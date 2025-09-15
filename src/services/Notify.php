<?php
namespace croacworks\essentials\services;

use Yii;
use yii\base\Component;
use yii\db\Query;
use yii\httpclient\Client;
use croacworks\essentials\models\Notification;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\User;
use croacworks\essentials\models\UserGroup;


/**
 * Serviço de criação e envio de notificações in-app (tabela `notifications`)
 * com suporte a:
 *  - entrega por usuário (fan-out por grupo opcional);
 *  - badge/lista (status lida/não lida por usuário);
 *  - push opcional via Expo (notificações móveis).
 *
 * Uso típico:
 *
 * ```php
 * // 1) Apenas criar (sem push)
 * Yii::$app->notify->create($userId, 'Título', 'Corpo', 'system', '/rota', $groupId);
 *
 * // 2) Criar e enviar push para um usuário
 * Yii::$app->notify->createAndPush($userId, 'Título', 'Corpo', 'system', '/rota');
 *
 * // 3) Broadcast por grupo (fan-out) — com push para todos
 * Yii::$app->notify->createForGroup($groupId, 'Aviso', 'Corpo', 'system', '/rota', true, null, true, ['foo'=>'bar']);
 * ```
 *
 * Convenções:
 * - `group_id` é persistido na notificação para rastrear a origem (tenant/escopo).
 * - Push Expo: tokens são resolvidos de `user_devices.expo_token` (status=1),
 *   `user.expo_token` ou `user.expoTokens` (array/JSON), se existirem.
 *
 * @package croacworks\essentials\services
 */

class Notify extends Component
{
    /* ======================== CRIAÇÃO ======================== */

    /**
     * Cria 1 notificação para 1 usuário (não dispara push).
     */
    public function create(
        int $toUserId,
        string $title,
        ?string $content = null,
        string $type = 'system',
        ?string $url = null,
        ?int $groupId = null
    ): ?Notification {
        $n = new Notification([
            'group_id'       => $groupId,
            'recipient_id'   => $toUserId,
            'recipient_type' => 'user',
            'type'           => $type,
            'description'    => $title,
            'content'        => $content,
            'url'            => $url,
        ]);

        if (!$n->validate()) {
            Yii::error(['notify.validate'=>false, 'errors'=>$n->errors, 'attrs'=>$n->attributes], 'notify');
            return null;
        }
        if (!$n->save(false)) {
            Yii::error(['notify.save'=>false, 'attrs'=>$n->attributes], 'notify');
            return null;
        }
        return $n;
    }

    /**
     * Cria e já dispara push Expo (se tokens forem encontrados/fornecidos).
     * $expoTokens pode ser string (1 token) ou array de tokens.
     */
    public function createAndPush(
        int $toUserId,
        string $title,
        ?string $content = null,
        string $type = 'system',
        ?string $url = null,
        ?int $groupId = null,
        string|array|null $expoTokens = null,
        array $expoData = []
    ): ?Notification {
        $n = $this->create($toUserId, $title, $content, $type, $url, $groupId);
        if (!$n) return null;

        // monta payload de dados (útil pra deep-link)
        $data = array_merge([
            'notification_id' => (int)$n->id,
            'title'           => (string)$title,
            'content'         => (string)($content ?? ''),
            'url'             => (string)($url ?? ''),
            'type'            => (string)$type,
        ], $expoData);

        if ($expoTokens === null) {
            $expoTokens = $this->getExpoTokensForUser($toUserId);
        }
        if ($expoTokens) {
            $this->sendExpoPush($expoTokens, $title, $content ?? '', $data);
        }
        return $n;
    }

    /**
     * Cria notificações para vários usuários (não dispara push).
     */
    public function createForUsers(
        array $userIds,
        string $title,
        ?string $content = null,
        string $type = 'system',
        ?string $url = null,
        ?int $groupId = null
    ): int {
        $count = 0;
        foreach (array_unique(array_map('intval', $userIds)) as $uid) {
            if ($uid <= 0) continue;
            if ($this->create($uid, $title, $content, $type, $url, $groupId)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Por grupo (fan-out): cria 1 por usuário; opcionalmente dispara push para todos.
     * $pushExpo=true → envia push para todos do grupo (e filhos se $includeChildren=true).
     * $expoData é anexado ao payload do push (ex.: ['foo'=>'bar']).
     */
    public function createForGroup(
        int $groupId,
        string $title,
        ?string $content = null,
        string $type = 'system',
        ?string $url = null,
        bool $includeChildren = true,
        ?int $persistGroupId = null,
        bool $pushExpo = false,
        array $expoData = []
    ): int {
        $groupIds = $includeChildren
            ? Group::getAllDescendantIds($groupId)
            : [(int)$groupId];

        $userIds  = $this->resolveUserIdsByGroups($groupIds);
        if (!$userIds) return 0;

        $gidToPersist = $persistGroupId ?? $groupId;

        // cria todas
        $created = $this->createForUsers($userIds, $title, $content, $type, $url, $gidToPersist);

        if ($pushExpo && $created > 0) {
            // agrega todos os tokens e envia em lote
            $tokens = [];
            foreach ($userIds as $uid) {
                foreach ($this->getExpoTokensForUser((int)$uid) as $t) {
                    $tokens[$t] = true;
                }
            }
            $tokens = array_keys($tokens);

            $data = array_merge([
                'title'   => (string)$title,
                'content' => (string)($content ?? ''),
                'url'     => (string)($url ?? ''),
                'type'    => (string)$type,
                'group_id'=> (int)$groupId,
            ], $expoData);

            if ($tokens) {
                $this->sendExpoPush($tokens, $title, $content ?? '', $data);
            }
        }

        return $created;
    }

    /* ======================== RESOLUÇÃO DE USUÁRIOS ======================== */

    /**
     * Resolve usuários pertencentes a QUALQUER grupo informado (group_id direto + pivô user_groups).
     */
    protected function resolveUserIdsByGroups(array $groupIds): array
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));
        if (empty($groupIds)) return [];

        $userTable = User::tableName();
        $ugTable   = UserGroup::tableName();

        $q = (new Query())
            ->select('u.id')
            ->from("$userTable u")
            ->leftJoin("$ugTable ug", 'ug.user_id = u.id')
            ->where(['or',
                ['u.group_id' => $groupIds],
                ['ug.group_id' => $groupIds],
            ])
            ->groupBy('u.id');

        if (property_exists(User::class, 'status') && defined(User::class.'::STATUS_ACTIVE')) {
            $q->andWhere(['u.status' => constant(User::class.'::STATUS_ACTIVE')]);
        }

        return $q->column();
    }

    /* ======================== EXPO PUSH ======================== */

    /**
     * Envia push via Expo.
     * $to pode ser string (1 token) ou array de tokens.
     * Retorna quantidade de mensagens aceitas pelo Expo.
     */
    public function sendExpoPush(string|array $to, string $title, string $body, array $data = []): int
    {
        $tokens = is_array($to) ? $to : [$to];
        // normaliza + valida
        $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens))));
        $tokens = array_values(array_filter($tokens, [$this, 'isValidExpoToken']));

        if (empty($tokens)) return 0;

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $accepted = 0;

        // Expo aceita ~100 mensagens por request; deixo margem
        $chunks = array_chunk($tokens, 99);
        foreach ($chunks as $chunk) {
            $messages = [];
            foreach ($chunk as $tk) {
                $messages[] = [
                    'to'    => $tk,
                    'title' => (string)$title,
                    'body'  => (string)$body,
                    'sound' => 'default',
                    'data'  => (array)$data,
                ];
            }

            try {
                $resp = $client->createRequest()
                    ->setMethod('POST')
                    ->setUrl('https://exp.host/--/api/v2/push/send')
                    ->setHeaders(['Content-Type' => 'application/json'])
                    ->setContent(json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->send();

                if (!$resp->isOk) {
                    Yii::error(['expo.send.errorHttp' => $resp->content], 'notify.expo');
                    continue;
                }

                // A API do Expo pode retornar array de tickets
                $payload = json_decode((string)$resp->content, true);
                if (is_array($payload)) {
                    // quando mandamos array de mensagens, geralmente o retorno já é array de tickets
                    $accepted += count($messages); // contabiliza tentativas; trate erros abaixo se quiser granular
                    // Você pode inspecionar $payload para checar "status":"ok" / "error"
                } else {
                    $accepted += count($messages);
                }
            } catch (\Throwable $e) {
                Yii::error(['expo.send.exception' => $e->getMessage()], 'notify.expo');
            }
        }

        return $accepted;
    }

    /**
     * Obtém tokens Expo de um usuário a partir do que existir na sua base:
     * - Tabela user_devices (modelo UserDevice) com coluna expo_token (status=1)
     * - Coluna User::expo_token (string)
     * - Propriedade/coluna User::expoTokens (array/JSON)
     */
    protected function getExpoTokensForUser(int $userId): array
    {
        $tokens = [];

        // 1) UserDevice (se existir)
        $userDeviceModel = null;
        if (class_exists('\\croacworks\\essentials\\models\\UserDevice')) {
            $userDeviceModel = '\\croacworks\\essentials\\models\\UserDevice';
        } elseif (class_exists('\\app\\models\\UserDevice')) {
            $userDeviceModel = '\\app\\models\\UserDevice';
        }

        if ($userDeviceModel) {
            try {
                /** @var \yii\db\ActiveRecord $userDeviceModel */
                $rows = $userDeviceModel::find()
                    ->select(['expo_token'])
                    ->where(['user_id' => $userId, 'status' => 1])
                    ->andWhere(['not', ['expo_token' => null]])
                    ->andWhere(['<>', 'expo_token', ''])
                    ->column();
                foreach ($rows as $tk) $tokens[] = (string)$tk;
            } catch (\Throwable $e) {
                // silencioso
            }
        }

        // 2) Campo direto no User (expo_token)
        try {
            $u = User::find()->select(['id','expo_token'])->where(['id'=>$userId])->one();
            if ($u && property_exists($u, 'expo_token') && !empty($u->expo_token)) {
                $tokens[] = (string)$u->expo_token;
            }
        } catch (\Throwable $e) {}

        // 3) Campo/prop expoTokens (array ou JSON)
        try {
            $u = $u ?? User::find()->select(['id'])->where(['id'=>$userId])->one();
            if ($u && property_exists($u, 'expoTokens') && !empty($u->expoTokens)) {
                $vals = is_string($u->expoTokens) ? json_decode($u->expoTokens, true) : $u->expoTokens;
                foreach ((array)$vals as $tk) $tokens[] = (string)$tk;
            }
        } catch (\Throwable $e) {}

        // normaliza/valida
        $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens))));
        $tokens = array_values(array_filter($tokens, [$this, 'isValidExpoToken']));
        return $tokens;
    }

    protected function isValidExpoToken(string $token): bool
    {
        // Aceita ExponentPushToken[...] e ExpoPushToken[...]
        return (bool)preg_match('#^(Exponent|Expo)PushToken\[[A-Za-z0-9+\-/_=.]+\]$#', $token);
    }
}
