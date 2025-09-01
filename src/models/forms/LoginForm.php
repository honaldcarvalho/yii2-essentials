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
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if (!$this->validate()) {
            return false;
        }

        // ==== BEGIN Soft License-Limit Gate (by user.group_id) ====
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $db = Yii::$app->db;
        $groupId = (int)$user->group_id;
        if ($groupId <= 0) {
            $this->addError('username', Yii::t('app', 'User has no base group assigned.'));
            return false;
        }

        // Carregar licença do grupo base (usa a coluna `validate`)
        $licenseRow = $db->createCommand('
        SELECT 
            l.id AS license_id,
            COALESCE(l.heartbeat_seconds, 600)              AS heartbeat_seconds,
            COALESCE(l.max_users_override, lt.max_devices)  AS limit_users,
            l.`validate`                                    AS `validate`
        FROM {{%licenses}} l
        JOIN {{%license_types}} lt ON lt.id = l.license_type_id
        WHERE l.group_id = :gid
        ORDER BY l.id DESC
        LIMIT 1
    ', [':gid' => $groupId])->queryOne();

        if (!$licenseRow) {
            $this->addError('username', Yii::t('app', 'License not found for this group.'));
            return false;
        }

        // Expiração baseada em `licenses.validate`
        $rawValidate = $licenseRow['validate'] ?? null;
        if ($rawValidate !== null && $rawValidate !== '') {
            // Aceita tanto string de data quanto timestamp numérico
            if (is_numeric($rawValidate)) {
                $ts = (int)$rawValidate;
            } else {
                $ts = @strtotime((string)$rawValidate);
                $ts = $ts === false ? 0 : $ts;
            }
            if ($ts > 0 && $ts < time()) {
                $this->addError('username', Yii::t('app', 'License expired for this group.'));
                return false;
            }
        }

        $heartbeat = (int)$licenseRow['heartbeat_seconds'];
        if ($heartbeat <= 0) {
            $heartbeat = 600; // fallback
        }
        $limit     = (int)$licenseRow['limit_users'];

        // Contar usuários distintos ativos na janela (Soft mode)
        try {
            $hb = max(1, (int)$heartbeat);
            $sql = "
                SELECT COUNT(DISTINCT uas.session_id)
                FROM {{%user_active_sessions}} uas
                WHERE uas.group_id = :gid
                AND uas.is_active = 1
                AND uas.last_seen_at > (NOW() - INTERVAL {$hb} SECOND)
            ";
            $activeCount = (int)$db->createCommand($sql, [':gid' => $groupId])->queryScalar();

            if ($limit > 0 && $activeAfter > $limit) {
                // estourou após este login -> desativar a sessão recém criada e falhar o login
                $db->createCommand('UPDATE {{%user_active_sessions}} SET is_active = 0 WHERE session_id = :sid', [
                    ':sid' => $sessionId
                ])->execute();

                Yii::$app->user->logout(false); // garante a sessão Yii destruída
                $this->addError('username', Yii::t('app', 'The limit of concurrent users for this group’s plan has been reached.'));
                return false;
            }
            
        } catch (\Throwable $e) {
            // Se a tabela ainda não existir, não bloqueia (rode a migration)
            $activeCount = 0;
        }

        if ($limit > 0 && $activeCount >= $limit) {
            $this->addError('username', Yii::t('app', 'The limit of concurrent users for this group’s plan has been reached.'));
            return false;
        }
        // ==== END Soft License-Limit Gate ====

        $duration = $this->rememberMe ? (3600 * 24 * 30) : 0;
        if (!Yii::$app->user->login($user, $duration)) {
            $this->addError('username', Yii::t('app', 'Login failed.'));
            return false;
        }

        // ==== BEGIN Register/Activate session in user_active_sessions ====
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
                ':gid' => $groupId,
                ':ip'  => $ip,
                ':ua'  => $ua,
            ])->execute();
        } catch (\Throwable $e) {
            // Ignora se tabela ainda não existir
        }
        // ==== END Register/Activate session ====

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
