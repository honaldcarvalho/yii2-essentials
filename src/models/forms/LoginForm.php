<?php

namespace croacworks\essentials\models\forms;

use croacworks\essentials\models\User;
use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }
    /**
     * Sobe na hierarquia a partir de $groupId (incluindo ele) até encontrar a primeira licença.
     * Retorna array com:
     * - license_group_id (int) → grupo “dono” da licença efetiva
     * - heartbeat_seconds (int)
     * - limit_users (int)
     * - validate (mixed|null)  → o mesmo que está em licenses.validate
     */
    public function resolveEffectiveLicense(int $groupId): ?array
    {
        $db = Yii::$app->db;
        $visited = [];
        $current = $groupId;

        while ($current && !in_array($current, $visited, true)) {
            $visited[] = $current;

            // Tenta pegar licença deste nó
            $row = $db->createCommand('
            SELECT 
                :gid AS license_group_id,
                COALESCE(l.heartbeat_seconds, 600)              AS heartbeat_seconds,
                COALESCE(l.max_users_override, lt.max_devices)  AS limit_users,
                l.`validate`                                    AS `validate`
            FROM {{%licenses}} l
            JOIN {{%license_types}} lt ON lt.id = l.license_type_id
            WHERE l.group_id = :gid
            ORDER BY l.id DESC
            LIMIT 1
        ', [':gid' => $current])->queryOne();

            if ($row) {
                return $row;
            }

            // Sobe para o pai
            $parent = $db->createCommand('
            SELECT parent_id FROM {{%groups}} WHERE id = :gid
        ', [':gid' => $current])->queryScalar();

            $current = $parent ? (int)$parent : 0;
        }

        return null; // nenhuma licença encontrada na cadeia
    }

    /**
     * Coleta **todos os group_ids** do sub-árvore (raiz incluída).
     * Estratégia iterativa (evita CTE para compatibilidade).
     */
    public function collectSubtreeGroupIds(int $rootGroupId): array
    {
        $db = Yii::$app->db;
        $result = [$rootGroupId];
        $frontier = [$rootGroupId];

        while (!empty($frontier)) {
            $children = $db->createCommand('
            SELECT id FROM {{%groups}} WHERE parent_id IN (' . implode(',', array_map('intval', $frontier)) . ')
        ')->queryColumn();

            $children = array_values(array_unique(array_map('intval', $children)));

            // remove já coletados
            $children = array_values(array_diff($children, $result));

            if (empty($children)) break;

            $result = array_merge($result, $children);
            $frontier = $children;
        }

        return $result;
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $db      = Yii::$app->db;
        $userId  = (int)$user->id;
        $groupId = (int)$user->group_id;

        // BYPASS: master/admin (considera base group_id e user_groups)
        try {
            if (User::isMasterByUserId($userId, $groupId, $db)) {
                $duration = $this->rememberMe ? (3600 * 24 * 30) : 0;
                if (!Yii::$app->user->login($user, $duration)) {
                    $this->addError('username', Yii::t('app', 'Login failed.'));
                    return false;
                }
                return true;
            }
        } catch (\Throwable $e) {
            // se falhar, segue fluxo normal sem bypass
        }

        if ($groupId <= 0) {
            $this->addError('username', Yii::t('app', 'User has no base group assigned.'));
            return false;
        }

        // === (1) Descobrir a licença efetiva subindo a hierarquia ===
        $effective = $this->resolveEffectiveLicense($groupId);
        if (!$effective) {
            $this->addError('username', Yii::t('app', 'No active license found in the group hierarchy.'));
            return false;
        }

        $licenseGroupId = (int)$effective['license_group_id'];
        $heartbeat      = (int)($effective['heartbeat_seconds'] ?? 600);
        if ($heartbeat <= 0) $heartbeat = 600;

        // Expiração baseada no campo `validate` da licença efetiva (se informado)
        $rawValidate = $effective['validate'] ?? null;
        if ($rawValidate !== null && $rawValidate !== '') {
            $ts = is_numeric($rawValidate) ? (int)$rawValidate : @strtotime((string)$rawValidate);
            $ts = $ts === false ? 0 : $ts;
            if ($ts > 0 && $ts < time()) {
                $this->addError('username', Yii::t('app', 'License expired for this group hierarchy.'));
                return false;
            }
        }

        // Limite de usuários (da licença efetiva, com fallback para Configuration)
        $limit = (int)($effective['limit_users'] ?? 0);
        if ($limit <= 0) {
            $cfg   = \croacworks\essentials\models\Configuration::get();
            $limit = (int)($cfg->max_user ?? 0);
        }

        // === (2) Contabilizar sessões na SUBÁRVORE do grupo licenciado ===
        $gids = $this->collectSubtreeGroupIds($licenseGroupId); // inclui o licenseGroupId
        $gids = array_values(array_unique(array_map('intval', $gids)));

        // Segurança: evita IN() vazio
        if (empty($gids)) $gids = [$licenseGroupId];

        $inList = implode(',', $gids);
        $activeCount = 0;
        try {
            $hb = max(1, (int)$heartbeat);
            $sql = "
            SELECT COUNT(DISTINCT uas.session_id)
            FROM {{%user_active_sessions}} uas
            WHERE uas.group_id IN ({$inList})
              AND uas.is_active = 1
              AND uas.last_seen_at > (NOW() - INTERVAL {$hb} SECOND)
        ";
            $activeCount = (int)$db->createCommand($sql)->queryScalar();
        } catch (\Throwable $e) {
            $activeCount = 0;
        }

        if ($limit > 0 && $activeCount >= $limit) {
            $this->addError('username', Yii::t('app', 'The limit of concurrent users for this license has been reached.'));
            return false;
        }

        // === Login Yii ===
        $duration = $this->rememberMe ? (3600 * 24 * 30) : 0;
        if (!Yii::$app->user->login($user, $duration)) {
            $this->addError('username', Yii::t('app', 'Login failed.'));
            return false;
        }

        // === Registrar/ativar sessão ===
        try {
            $sessionId = Yii::$app->session->id;
            $ip        = Yii::$app->request->userIP;
            $ua        = substr((string)Yii::$app->request->userAgent, 0, 255);

            $db->createCommand('
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
        ', [
                ':sid' => $sessionId,
                ':uid' => (int)$user->id,
                ':gid' => $groupId, // mantém o grupo real do usuário
                ':ip'  => $ip,
                ':ua'  => $ua,
            ])->execute();
        } catch (\Throwable $e) {
            // silencioso (tabela pode não existir ainda)
        }

        // === (3) RECHECK anti-condição de corrida, ainda na SUBÁRVORE ===
        try {
            $hb = max(1, (int)$heartbeat);
            $sqlRecheck = "
            SELECT COUNT(DISTINCT uas.session_id)
            FROM {{%user_active_sessions}} uas
            WHERE uas.group_id IN ({$inList})
              AND uas.is_active = 1
              AND uas.last_seen_at > (NOW() - INTERVAL {$hb} SECOND)
        ";
            $activeAfter = (int)$db->createCommand($sqlRecheck)->queryScalar();

            if ($limit > 0 && $activeAfter > $limit) {
                // estourou após registrar -> desativar esta sessão e sair
                $db->createCommand('UPDATE {{%user_active_sessions}} SET is_active = 0 WHERE session_id = :sid', [
                    ':sid' => $sessionId
                ])->execute();

                Yii::$app->user->logout(false);
                $this->addError('username', Yii::t('app', 'The limit of concurrent users for this license has been reached.'));
                return false;
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        return true;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
