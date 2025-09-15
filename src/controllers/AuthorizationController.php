<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\Group;
use Yii;

use croacworks\essentials\models\Log;
use croacworks\essentials\models\Role;
use croacworks\essentials\models\User;

use yii\behaviors\TimestampBehavior;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

use yii\db\Query;
use yii\db\IntegrityException;
use yii\db\ActiveRecord;

class AuthorizationController extends CommonController
{
    // Mantido para compatibilidade
    const ADMIN_GROUP_ID = 2;

    public $free = ['login', 'signup', 'error'];

    /* ===== Helpers de usuário/grupos (compatíveis com o que já existe) ===== */

    public static function isGuest(): bool
    {
        return Yii::$app->user->isGuest;
    }

    public static function User(): ?User
    {
        return Yii::$app->user->identity;
    }

    /**
     * Grupo principal do usuário (sem alterar estrutura atual)
     */
    public static function getPrimaryGroupId(): ?int
    {
        return self::User()?->group_id;
    }

    /**
     * Todos os grupos do usuário: principal primeiro, depois adicionais (ordenados).
     * Mantém o método getUserGroupsId() existente, apenas reorganizando.
     */
    public static function getAllUserGroupIds(): array
    {
        $u = self::User();
        if (!$u) return [];

        $primary = (int)$u->group_id;
        $others  = array_values(array_diff(array_map('intval', $u->getUserGroupsId()), [$primary]));
        sort($others, SORT_NUMERIC);

        return array_merge([$primary], $others);
    }

    /**
     * Mantido para compatibilidade com pontos do código que usem esse método.
     * Agora ele só devolve o último grupo como antes (não quebramos nada),
     * mas internamente passamos a usar getAllUserGroupIds() para autorização.
     */
    public static function userGroup(): ?int
    {
        // 1) Tenta obter o usuário: via bearer token OU sessão
        $user = self::getUserByToken() ?: Yii::$app->user->identity ?? null;
        if (!$user) {
            return null;
        }

        // 2) Colete TODOS os grupos do usuário (pivot + principal)
        //    Obs: se o método já existia, manter o nome pra não quebrar nada.
        $ids = $user->getUserGroupsId(); // garantiremos que inclui $user->group_id (ver passo 3)

        // 3) Se não há nenhum id (usuário órfão?), caia pro principal
        if (empty($ids)) {
            return (int)($user->group_id ?? 0) ?: null;
        }

        // 4) Se a flag de rollout estiver ativa, preferir o ROOT (pai). Senão, manter comportamento legado.
        $preferRoot = (bool)(Yii::$app->params['auth.preferRootGroup'] ?? true);

        if ($preferRoot) {
            // preferir o ancestral raiz
            return self::preferRootGroupId($ids, (int)$user->group_id);
        }

        // comportamento legado (o “end()” antigo)
        // ⚠️ Não use mais end($ids) direto: seja explícito.
        $last = null;
        foreach ($ids as $id) {
            $last = $id;
        }
        return $last ?: (int)$user->group_id;
    }

    /**
     * Encontra o id raiz (ancestral mais alto) dentro do conjunto $candidateIds.
     * Se não for possível determinar pela árvore, cai no $fallback (group principal do usuário).
     */
    private static function preferRootGroupId(array $candidateIds, int $fallback = 0): ?int
    {
        $candidateIds = array_values(array_unique(array_map('intval', $candidateIds)));
        if (empty($candidateIds)) {
            return $fallback ?: null;
        }

        // Carrega pares id=>parent_id para os candidatos
        $rows = (new \yii\db\Query())
            ->select(['id', 'parent_id'])
            ->from(\croacworks\essentials\models\Group::tableName())
            ->where(['id' => $candidateIds])
            ->all();

        // Monte um mapa id => parent_id
        $parentById = [];
        foreach ($rows as $r) {
            $parentById[(int)$r['id']] = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
        }

        // Um "root" é aquele cujo parent_id NÃO está no conjunto
        $candidateSet = array_flip($candidateIds);
        $roots = [];
        foreach ($candidateIds as $id) {
            $pid = $parentById[$id] ?? null;
            if ($pid === null || !isset($candidateSet[$pid])) {
                $roots[] = $id;
            }
        }

        if (!empty($roots)) {
            // Se houver múltiplos roots, escolha o mais ancestral determinístico.
            // Critério simples e estável: o menor id (evita efeitos colaterais de ordem).
            sort($roots, SORT_NUMERIC);
            return (int)$roots[0];
        }

        // Se não achou root claro (ciclo, dados ruins, etc.), usa fallback
        return $fallback ?: (int)reset($candidateIds);
    }

    public static function getUserGroups()
    {
        return self::isGuest()
            ? self::getUserByToken()?->getUserGroupsId()
            : self::User()?->getUserGroupsId();
    }


    /**
     * Current effective group id (base group) for scoping.
     * Ajuste se uses "current group" por token/URL/selector.
     */
    public static function currentGroupId(): ?int
    {
        $user = Yii::$app->user->identity ?? null;
        if (!$user) return null;

        // Base case: usa group_id do usuário (teu padrão atual).
        // Se você já implementa "group ativo" via sessão/token, substitua aqui.
        return (int)($user->group_id ?? 0) ?: null;
    }

    /**
     * Returns all group IDs within the same family (root tree) as current group.
     * Cache curto para reduzir hits.
     */
    public static function groupScopeIds(): array
    {
        $gid = static::currentGroupId();
        if (!$gid) return [];

        $cacheKey = 'group-family:' . $gid;
        $ids = Yii::$app->cache->get($cacheKey);
        if ($ids === false) {
            try {
                $ids = Group::familyIds($gid);
            } catch (\Throwable $e) {
                // Fallback se a instância não suporta CTE
                $ids = Group::familyIdsNoCte($gid);
            }
            Yii::$app->cache->set($cacheKey, $ids, 60); // 60s de cache (ajuste à vontade)
        }

        return $ids ?: [$gid];
    }

    public static function isMaster(): bool
    {
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }

        $userId = Yii::$app->user->id;
        if (!$userId) {
            return $memo = false;
        }

        $db = Yii::$app->db;

        // Tabelas (sem disparar AR::find())
        $groupsTable = \croacworks\essentials\models\Group::tableName();
        $ugTable = class_exists(\croacworks\essentials\models\UserGroup::class)
            ? \croacworks\essentials\models\UserGroup::tableName()
            : '{{%user_groups}}'; // fallback

        // Pega o group_id direto do identity (não faz query extra)
        $directGroupId = null;
        if (($identity = Yii::$app->user->identity) && property_exists($identity, 'group_id')) {
            $directGroupId = $identity->group_id ?: null;
        }

        // Subquery: todos os grupos ligados via user_groups
        $subUserGroups = (new \yii\db\Query())
            ->select('ug.group_id')
            ->from("$ugTable ug")
            ->where(['ug.user_id' => $userId]);

        // Condição: grupo master se (g.id = group_id direto) OU (g.id IN user_groups)
        $orCondition = ['or'];
        if ($directGroupId) {
            $orCondition[] = ['g.id' => (int)$directGroupId];
        }
        $orCondition[] = ['in', 'g.id', $subUserGroups];

        // Consulta única: existe algum grupo 'master' que atenda uma das condições acima?
        $exists = (new \yii\db\Query())
            ->from("$groupsTable g")
            ->where(['g.level' => 'master'])
            ->andWhere($orCondition)
            ->limit(1)
            ->exists($db);

        return $memo = (bool)$exists;
    }

    public static function isAdmin(): bool
    {
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }

        $userId = Yii::$app->user->id;
        if (!$userId) {
            return $memo = false;
        }

        $db = Yii::$app->db;

        // Tabelas (sem disparar AR::find())
        $groupsTable = \croacworks\essentials\models\Group::tableName();
        $ugTable = class_exists(\croacworks\essentials\models\UserGroup::class)
            ? \croacworks\essentials\models\UserGroup::tableName()
            : '{{%user_groups}}'; // fallback

        // Pega o group_id direto do identity (não faz query extra)
        $directGroupId = null;
        if (($identity = Yii::$app->user->identity) && property_exists($identity, 'group_id')) {
            $directGroupId = $identity->group_id ?: null;
        }

        // Subquery: todos os grupos ligados via user_groups
        $subUserGroups = (new \yii\db\Query())
            ->select('ug.group_id')
            ->from("$ugTable ug")
            ->where(['ug.user_id' => $userId]);

        // Condição: grupo master se (g.id = group_id direto) OU (g.id IN user_groups)
        $orCondition = ['or'];
        if ($directGroupId) {
            $orCondition[] = ['g.id' => (int)$directGroupId];
        }
        $orCondition[] = ['in', 'g.id', $subUserGroups];

        // Consulta única: existe algum grupo 'master' que atenda uma das condições acima?
        $exists = (new \yii\db\Query())
            ->from("$groupsTable g")
            ->where(['g.level' => 'admin'])
            ->andWhere($orCondition)
            ->limit(1)
            ->exists($db);

        return $memo = (bool)$exists;
    }

    public static function getUserByToken()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return User::find()
                ->where(['status' => User::STATUS_ACTIVE])
                ->andWhere(['or', ['access_token' => $matches[1]], ['auth_key' => $matches[1]]])
                ->one();
        }
        return null;
    }

    /* ============================================================
     *  GLOBAL FK CHECKS / DELETE GUARD
     * ============================================================ */

    /**
     * Scan DB for references to a given record.
     * - Detects real FKs referencing $refTable.$refPk
     * - Optionally checks heuristic columns (e.g. ['file_id']) across all tables
     *
     * @param string $refTable     e.g. File::tableName()
     * @param string $refPk        e.g. 'id'
     * @param int|string $value    primary key value
     * @param string[] $heuristicColumns columns to probe by equality (optional)
     * @return array [['table'=>..., 'column'=>..., 'count'=>int], ...]
     */
    public static function findReferences(string $refTable, string $refPk, $value, array $heuristicColumns = []): array
    {
        $db = Yii::$app->db;
        $schema = $db->schema;
        $tables = $schema->getTableSchemas();
        $refs = [];

        // 1) Real foreign keys referencing $refTable.$refPk
        foreach ($tables as $tbl) {
            foreach ($tbl->foreignKeys as $fk) {
                $fkRefTable = $fk[0] ?? null;
                if ($fkRefTable !== $refTable) {
                    continue;
                }
                foreach ($fk as $localCol => $refCol) {
                    if ($localCol === 0) continue;
                    if ($refCol === $refPk) {
                        $exists = (new Query())
                            ->from($tbl->name)
                            ->where([$localCol => $value])
                            ->limit(1)
                            ->exists($db);
                        if ($exists) {
                            $refs[] = [
                                'table'  => $tbl->name,
                                'column' => $localCol,
                                'count'  => 1,
                                'type'   => 'fk',
                            ];
                        }
                    }
                }
            }
        }

        // 2) Heuristic columns (e.g. 'file_id')
        if (!empty($heuristicColumns)) {
            foreach ($tables as $tbl) {
                foreach ($heuristicColumns as $col) {
                    // skip if column doesn't exist
                    if ($tbl->getColumn($col) === null) {
                        continue;
                    }
                    // avoid duplicates if already captured via FK
                    $already = array_filter(
                        $refs,
                        fn($r) =>
                        $r['table'] === $tbl->name && $r['column'] === $col
                    );
                    if ($already) continue;

                    $exists = (new Query())
                        ->from($tbl->name)
                        ->where([$col => $value])
                        ->limit(1)
                        ->exists($db);

                    if ($exists) {
                        $refs[] = [
                            'table'  => $tbl->name,
                            'column' => $col,
                            'count'  => 1,
                            'type'   => 'heuristic',
                        ];
                    }
                }
            }
        }

        return $refs;
    }

    /**
     * Shortcut to check if a model can be deleted.
     *
     * @param ActiveRecord $model
     * @param string[] $heuristicColumns e.g. ['file_id'] when deleting File
     * @param string|null $pkAttr defaults to 'id'
     * @return array ['allowed'=>bool, 'refs'=>array]
     */
    public static function canDeleteModel(ActiveRecord $model, array $heuristicColumns = [], ?string $pkAttr = 'id'): array
    {
        $table = $model::tableName();
        $pkAttr = $pkAttr ?? 'id';
        $pkValue = $model->getAttribute($pkAttr);

        // Nota: suporte a PK composta não está contemplado neste helper
        $refs = self::findReferences($table, $pkAttr, $pkValue, $heuristicColumns);
        return ['allowed' => empty($refs), 'refs' => $refs];
    }

    /**
     * Standard JSON response for blocked deletions.
     *
     * @param array $refs result from findReferences()
     * @param string|null $friendly What the record is (for message), e.g. 'Arquivo'
     * @return array
     */
    public static function blockedDeleteResponse(array $refs, ?string $friendly = null): array
    {
        $label = $friendly ?: Yii::t('app', 'Record');
        $list  = array_map(
            fn($r) => "{$r['table']}.{$r['column']}" . (isset($r['type']) ? " ({$r['type']})" : ''),
            $refs
        );
        $message = Yii::t(
            'app',
            '{item} está em uso por outras tabelas. Remova os vínculos antes de excluir.',
            ['item' => $label]
        );

        return [
            'success' => false,
            'error'   => [
                'type'    => 'db.foreign_key',
                'message' => $message,
                'refs'    => $refs,
                'hints'   => [
                    Yii::t('app', 'Referências encontradas: {refs}', ['refs' => implode(', ', $list)]),
                ],
            ],
        ];
    }

    /**
     * Guard a delete operation:
     *  - Pre-check references
     *  - Try delete in a transaction
     *  - On IntegrityException, re-scan references and return blocked response
     *
     * @param ActiveRecord $model
     * @param string[] $heuristicColumns e.g. ['file_id'] for File deletion
     * @param callable|null $beforeDelete optional hook: fn(ActiveRecord $model){...}
     * @param callable|null $afterDelete  optional hook: fn(ActiveRecord $model){...}
     * @param string|null $friendlyLabel  e.g. 'Arquivo'
     * @return array JSON payload
     */
    public static function guardDelete(
        ActiveRecord $model,
        array $heuristicColumns = [],
        ?callable $beforeDelete = null,
        ?callable $afterDelete  = null,
        ?string $friendlyLabel  = null
    ): array {
        $check = self::canDeleteModel($model, $heuristicColumns);
        if (!($check['allowed'] ?? false)) {
            return self::blockedDeleteResponse($check['refs'], $friendlyLabel);
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            if ($beforeDelete) {
                $beforeDelete($model);
            }

            if ($model->delete() === false) {
                $tx->rollBack();
                return [
                    'success' => false,
                    'error'   => [
                        'type'    => 'db.delete_failed',
                        'message' => Yii::t('app', 'Falha ao excluir.'),
                        'model'   => $model::class,
                        'id'      => $model->getPrimaryKey(),
                    ],
                ];
            }

            if ($afterDelete) {
                $afterDelete($model);
            }

            $tx->commit();
            return ['success' => true];
        } catch (IntegrityException $e) {
            $tx->rollBack();
            // Re-scan to inform precisely where it’s referenced
            $table = $model::tableName();
            $pkAttr = is_array($model->primaryKey()) ? 'id' : $model->primaryKey()[0] ?? 'id';
            $refs = self::findReferences($table, $pkAttr, $model->getAttribute($pkAttr), $heuristicColumns);

            return self::blockedDeleteResponse($refs, $friendlyLabel);
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error'   => [
                    'type'    => 'server.error',
                    'message' => Yii::t('app', 'Erro inesperado ao excluir.'),
                ],
            ];
        }
    }
    /* ===== Behaviors (apenas adições, sem remover o que você já usa) ===== */

    public function behaviors()
    {
        $b = parent::behaviors();

        // Verbos (mantém OPTIONS liberado em rotas típicas de auth)
        $b['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'login'  => ['POST', 'OPTIONS'],
                'signup' => ['POST', 'OPTIONS'],
            ],
        ];

        // Timestamp (mantém seu comportamento)
        $b['timestamp'] = [
            'class' => TimestampBehavior::class,
            'value' => fn() => date('Y-m-d H:i:s'),
        ];

        // AccessControl com matchCallback (licença + overlay + admin)
        $b['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => $this->free,
                    'roles' => ['?', '@'],
                ],
                [
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => function () {
                        if (self::isMaster()) return true;

                        // Licença
                        if ($this->verifyLicense() === null) {
                            Yii::$app->session->setFlash('warning', Yii::t('app', 'License expired!'));
                            return false;
                        }

                        // Autorização por overlay
                        return $this->pageAuth();
                    },
                ],
            ],
        ];

        // Logging seguro (mantém sua lógica, com máscara básica)
        if ($this->config->logging && $this->id != 'log') {
            if (Yii::$app->user->identity !== null) {
                $this->logRequestSafe();
            }
        }

        return $b;
    }

    /* ===== Autorização por página (overlay) ===== */

    public function pageAuth(): bool
    {
        if (self::isGuest()) return false;
        if (self::isMaster()) return true;

        $controllerFQCN = static::getClassPath();
        $action         = Yii::$app->controller->action->id;

        // cache leve por request
        static $memo = [];
        $key = $controllerFQCN . '#' . $action . '#' . (self::User()?->id ?? 0);
        if (array_key_exists($key, $memo)) return $memo[$key];

        return $memo[$key] = $this->isActionAllowedByOverlay($controllerFQCN, $action);
    }

    /**
     * Overlay: grupo principal -> grupos adicionais (ordenados).
     * Allows adicionam, denies (prefixo "-") removem. "*" expande para todas as actions do controller.
     */
    protected function isActionAllowedByOverlay(string $controllerFQCN, string $action): bool
    {
        $groups = self::getAllUserGroupIds();
        if (empty($groups)) return false;

        $allControllerActions = $this->listControllerActions($controllerFQCN);
        $effective = []; // mapa action => true

        foreach ($groups as $gId) {
            /** @var Role[] $roles */
            $roles = Role::find()
                ->where(['controller' => $controllerFQCN, 'group_id' => $gId, 'status' => 1])
                ->all();

            foreach ($roles as $r) {
                $tokens = $this->parseActionsTokens((string)$r->actions);

                // allow tudo
                if (in_array('*', $tokens, true)) {
                    foreach ($allControllerActions as $a) {
                        $effective[$a] = true;
                    }
                }

                // allows explícitos
                foreach ($tokens as $t) {
                    if ($t === '' || $t === '*' || $t[0] === '-') continue;
                    $effective[$t] = true;
                }

                // denies explícitos (precedência)
                foreach ($tokens as $t) {
                    if (isset($t[0]) && $t[0] === '-') {
                        $name = ltrim($t, '-');
                        unset($effective[$name]);
                    }
                }
            }
        }

        return !empty($effective[$action]);
    }

    /**
     * Converte "index;view;-delete;*" em tokens normalizados.
     */
    protected function parseActionsTokens(string $raw): array
    {
        $parts = array_filter(array_map('trim', explode(';', $raw)));
        $out = [];
        foreach ($parts as $p) {
            $p = mb_strtolower($p);
            if ($p === '*') {
                $out[] = '*';
                continue;
            }
            if (str_starts_with($p, '-')) {
                $out[] = '-' . ltrim($p, '-');
                continue;
            }
            $out[] = $p;
        }
        return $out;
    }

    /**
     * Lista as actions públicas do controller (via reflexão).
     * Ex.: actionIndex => "index", actionCreate => "create", etc.
     */
    protected function listControllerActions(string $controllerFQCN): array
    {
        $ids = [];
        try {
            $rc = new \ReflectionClass($controllerFQCN);
            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->isStatic()) continue;
                $name = $m->getName();
                if (!str_starts_with($name, 'action')) continue;
                $id = $this->extractActionId($name);
                if ($id) $ids[] = $id;
            }
        } catch (\Throwable $e) {
            // fallback mínimo
            $ids = ['index', 'view', 'create', 'update', 'delete'];
        }
        return array_values(array_unique($ids));
    }

    protected function extractActionId(string $methodName): ?string
    {
        if (!str_starts_with($methodName, 'action')) return null;
        $id = substr($methodName, 6); // remove 'action'
        if ($id === '') return null;
        $id = preg_replace('/([a-z])([A-Z])/', '$1-$2', $id);
        return mb_strtolower($id);
    }

    /* ===== Autorização programática (compatível) ===== */

    public static function verAuthorization($controllerFQCN, $request_action, $model = null): bool
    {
        if (self::isGuest()) return false;
        if (self::isMaster()) return true;

        if (self::verifyLicense() === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'License expired!'));
            return false;
        }

        // ⬇️ AQUI: usar família (próprio + ancestrais), não só os grupos do usuário
        if ($model && $model->verGroup) {
            $groups = \croacworks\essentials\models\Group::familyIdsFromUser(self::User());
            $groups = array_map('intval', (array)$groups);

            if ($request_action === 'view' && (int)$model->group_id === 1) {
                // público
            } elseif (!in_array((int)$model->group_id, $groups, true)) {
                return false;
            }
        }

        /** @var self $ctrl */
        $ctrl = Yii::$app->controller instanceof self
            ? Yii::$app->controller
            : new self(Yii::$app->controller->id, Yii::$app->controller->module);

        return $ctrl->isActionAllowedByOverlay($controllerFQCN, $request_action);
    }


    /* ===== Licença (mantida) ===== */

    // Substitua seu método atual:
    public static function verifyLicense()
    {
        // Admin passa sempre
        if (self::isMaster()) {
            return true;
        }

        $u = self::User();
        if (!$u || !$u->group_id) {
            return null; // sem usuário ou sem grupo principal
        }

        $db = \Yii::$app->db;

        // Evita loops acidentais em hierarquias corrompidas
        $visited = [];
        $current = (int)$u->group_id;

        while ($current && !in_array($current, $visited, true)) {
            $visited[] = $current;

            // Busca a licença mais "recente" deste grupo (sem filtrar por data aqui)
            /** @var \croacworks\essentials\models\License|null $lic */
            $lic = \croacworks\essentials\models\License::find()
                ->where(['group_id' => $current, 'status' => 1])
                ->orderBy(['validate' => SORT_DESC, 'id' => SORT_DESC])
                ->one();

            // Validação flexível do campo `validate`:
            // - null/'' => ilimitada (considera válida)
            // - numérica => timestamp UNIX
            // - string => parse via strtotime
            if ($lic) {
                $raw = $lic->validate ?? null;

                $isValid = true; // default: sem data -> válido
                if ($raw !== null && $raw !== '') {
                    if (is_numeric($raw)) {
                        $ts = (int)$raw;
                    } else {
                        $ts = @strtotime((string)$raw);
                        $ts = $ts === false ? 0 : $ts;
                    }
                    // Se tiver timestamp válido e estiver no passado, invalida
                    if ($ts > 0 && $ts < time()) {
                        $isValid = false;
                    }
                }

                if ($isValid) {
                    // Encontramos a licença efetiva neste ancestral
                    return $lic;
                }
            }

            // Sobe para o pai
            $parent = $db->createCommand('SELECT parent_id FROM {{%groups}} WHERE id = :gid', [
                ':gid' => $current
            ])->queryScalar();

            $current = $parent ? (int)$parent : 0;
        }

        // Nenhuma licença válida encontrada na cadeia
        return null;
    }


    // (Opcional, se quiser ler como booleano em matchCallback)
    public static function verifyLicenseBool(): bool
    {
        if (self::isMaster()) return true;
        return self::verifyLicense() !== null;
    }

    public static function logAuth(string $type, ?string $username, bool $success, ?string $reason = null): void
    {
        // $type: 'login' | 'logout'
        try {
            $req = \Yii::$app->request;

            $uname = trim((string)$username);
            if ($uname === '') {
                $post = $req->post();
                $uname = (string)($post['LoginForm']['username'] ?? $post['username'] ?? '(unknown)');
            }

            $log = new \croacworks\essentials\models\Log();
            $log->action     = $type; // 'login' ou 'logout'
            $log->controller = \Yii::$app->controller?->id ?? 'site';
            $log->ip         = $req->userIP;
            $log->device     = method_exists(\Yii::$app->controller, 'getOS')
                ? \Yii::$app->controller->getOS()
                : ($req->userAgent ?? '');
            $log->user_id    = \Yii::$app->user->id ?? null;

            $log->data = json_encode([
                'username' => $uname,
                'success'  => (bool)$success,
                'reason'   => $reason,
                'route'    => \Yii::$app->requestedRoute,
                'method'   => strtoupper($req->method ?? 'GET'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $log->save(false);
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    /* ===== Log seguro (mantido, com máscara leve) ===== */

    protected function logRequestSafe(): void
    {
        $request = Yii::$app->request;
        $method  = strtoupper($request->method ?? 'GET');

        // Só loga operações de escrita
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

        $maskKeys    = ['password', 'senha', 'token', 'secret', 'authorization', 'access_token'];
        $ignoreAttrs = ['created_at', 'updated_at', 'created_by', 'updated_by'];

        $isSensitive = static function (string $key) use ($maskKeys): bool {
            $lk = mb_strtolower($key);
            foreach ($maskKeys as $s) if (str_contains($lk, $s)) return true;
            return false;
        };
        $normalize = static function ($v) {
            if (is_string($v)) {
                $v = trim($v);
                if ($v === '') return null;
                return $v;
            }
            if (is_bool($v)) return $v ? '1' : '0';
            if (is_numeric($v)) return (string)$v;
            if ($v === '' || $v === []) return null;
            if (is_array($v) || is_object($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $v === null ? null : (string)$v;
        };

        try {
            $payload = $request->post();
            if (empty($payload)) return;

            // 1) Detecta form principal (primeiro array no root)
            $formKey = null;
            $formData = null;
            foreach ($payload as $k => $v) {
                if (is_array($v)) {
                    $formKey = (string)$k;
                    $formData = $v;
                    break;
                }
            }
            if (!is_array($formData)) return;

            // mascara sensíveis no formulário
            foreach ($formData as $k => &$v) if ($isSensitive($k)) {
                $v = '***';
            }
            unset($v);

            // 2) Descobre id
            $id = $request->get('id') ?? ($formData['id'] ?? $payload['id'] ?? null);
            if ($id !== null && ctype_digit((string)$id)) $id = (int)$id;

            // 3) Resolve modelClass
            $modelClass = null;
            if (property_exists(Yii::$app->controller, 'modelClass')) {
                $tmp = Yii::$app->controller->modelClass;
                if ($tmp && class_exists($tmp) && is_subclass_of($tmp, \yii\db\ActiveRecord::class)) {
                    $modelClass = $tmp;
                }
            }
            if ($modelClass === null && $formKey) {
                // tenta namespaces comuns (simples e sem helpers)
                foreach (
                    [
                        'app\\models\\',
                        'common\\models\\',
                        'croacworks\\essentials\\models\\',
                        'weebz\\yii2basics\\models\\',
                    ] as $ns
                ) {
                    $cand = $ns . $formKey;
                    if (class_exists($cand) && is_subclass_of($cand, \yii\db\ActiveRecord::class)) {
                        $modelClass = $cand;
                        break;
                    }
                }
            }

            $changes = [];
            if ($modelClass) {
                // 4) Busca "old" com 2 tentativas:
                // 4a) via AR (respeita escopos)
                /** @var \yii\db\ActiveRecord|null $oldModel */
                $oldModel = $id ? $modelClass::findOne($id) : null;
                $oldAttrs = $oldModel ? $oldModel->getAttributes() : [];

                // 4b) fallback SQL direto (ignora escopos)
                if (!$oldModel && $id !== null) {
                    /** @var \yii\db\ActiveRecord $mc */
                    $mc = $modelClass;
                    $table = $mc::tableName();                          // ex: {{%page}}
                    $pk    = $mc::primaryKey()[0] ?? 'id';

                    $oldRow = (new \yii\db\Query())
                        ->from($table)
                        ->where([$pk => $id])
                        ->one();

                    if (is_array($oldRow)) $oldAttrs = $oldRow;
                }

                // 5) Monta diffs só de atributos que realmente mudaram
                foreach ($formData as $attr => $newVal) {
                    if (in_array($attr, $ignoreAttrs, true)) continue;

                    $to   = $isSensitive($attr) ? '***' : $normalize($newVal);
                    $from = $isSensitive($attr) ? '***' : $normalize($oldAttrs[$attr] ?? null);

                    if ($from !== $to) {
                        $changes[] = ['attr' => (string)$attr, 'from' => $from, 'to' => $to];
                    }
                }
            } else {
                // sem modelClass: from vai ser null por não ter fonte "old"
                foreach ($formData as $attr => $newVal) {
                    if (in_array($attr, $ignoreAttrs, true)) continue;
                    $to = $isSensitive($attr) ? '***' : $normalize($newVal);
                    if ($to !== null) $changes[] = ['attr' => (string)$attr, 'from' => null, 'to' => $to];
                }
            }

            if (empty($changes)) return; // nada mudou → não grava

            $diffPayload = [
                'model'   => $modelClass ?: $formKey,
                'id'      => $id,
                'changes' => $changes,
                'action'  => Yii::$app->controller->action->id ?? '',
                'route'   => Yii::$app->requestedRoute,
                'method'  => $method,
            ];

            $dataJson = json_encode($diffPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (strlen($dataJson) > 8192) $dataJson = substr($dataJson, 0, 8192) . '…';

            $log = new Log();
            $log->action     = $diffPayload['action'];
            $log->ip         = $this->getUserIP();
            $log->device     = $this->getOS();
            $log->controller = Yii::$app->controller->id;
            $log->user_id    = Yii::$app->user->identity->id ?? null;
            $log->data       = $dataJson;
            $log->save(false);
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    /* ===== findModel (mantido, só usando getAllUserGroupIds) ===== */

    protected function findModel($id, $model_name = null)
    {
        $modelClass = $model_name ?? str_replace(['controllers', 'Controller'], ['models', ''], static::getClassPath());

        if (!class_exists($modelClass)) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Model class not found.'));
        }

        // MASTER: ignora qualquer escopo de grupo (usa find(false))
        if (self::isMaster()) {

            $instance = $modelClass::find(false)->where(['id' => (int)$id])->limit(1)->one();
            if ($instance !== null) return $instance;
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }

        // NÃO-MASTER: deixa o ModelCommon aplicar o escopo; não duplique filtros aqui
        $query = $modelClass::find()->where(['id' => (int)$id])->limit(1);

        // Se você realmente quer liberar o grupo público (1) só no "view",
        // isso já é tratado no seu ModelCommon (você adiciona 1 ao array). 
        // Evite refazer aqui para não divergir do escopo global.

        $instance = $query->one();
        if ($instance !== null) return $instance;

        throw new \yii\web\NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /** 
     * Heartbeat SOFT: registra/atualiza presença por sessão usando SEMPRE user.group_id.
     * Não derruba sessões e NÃO alterna grupo no contexto desta sessão.
     * Mantém todas as regras de autorização existentes.
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Ações livres não precisam de heartbeat
        if (in_array($action->id, (array)$this->free, true)) {
            return true;
        }

        if (!Yii::$app->user->isGuest) {
            $user = self::User();
            $groupId = (int)($user->group_id ?? 0);
            if ($groupId > 0) {
                $this->upsertHeartbeat(
                    Yii::$app->session->id,
                    (int)$user->id,
                    $groupId,
                    Yii::$app->request->userIP,
                    (string)substr((string)Yii::$app->request->userAgent, 0, 255)
                );
            }
        }

        return true;
    }

    /**
     * UPSERT de heartbeat em user_active_sessions.
     * Requer índice UNIQUE em session_id conforme migration sugerida.
     * Não alterna group_id por sessão (regra: SEMPRE user.group_id para presença/licença).
     */
    protected function upsertHeartbeat(string $sessionId, int $userId, int $groupId, ?string $ip, ?string $userAgent): void
    {
        try {
            Yii::$app->db->createCommand(" 
                INSERT INTO {{%user_active_sessions}}
                    (session_id, user_id, group_id, ip, user_agent, created_at, last_seen_at, is_active)
                VALUES
                    (:sid, :uid, :gid, :ip, :ua, NOW(), NOW(), 1)
                ON DUPLICATE KEY UPDATE
                    user_id      = VALUES(user_id),
                    group_id     = VALUES(group_id),
                    ip           = VALUES(ip),
                    user_agent   = VALUES(user_agent),
                    last_seen_at = VALUES(last_seen_at),
                    is_active    = 1
            ", [
                ':sid' => $sessionId,
                ':uid' => $userId,
                ':gid' => $groupId,
                ':ip'  => $ip,
                ':ua'  => $userAgent,
            ])->execute();
        } catch (\Throwable $e) {
            // Fail-safe: não quebra o fluxo da página se a tabela ainda não existir
            Yii::debug('[heartbeat] ' . $e->getMessage(), __METHOD__);
        }
    }
}
