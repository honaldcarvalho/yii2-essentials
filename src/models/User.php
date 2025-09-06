<?php

namespace croacworks\essentials\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property int|null $group_id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property string $access_token
 * @property string|null $token_validate
 * @property string|null $password_reset_token
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 *
 * @property Log[] $logs
 * @property Notification[] $notifications
 * @property Role[] $roles
 * @property UserProfile $profile
 * @property UsersGroup[] $usersGroups
 * @property Group $group
 */

class User extends Account
{
    public $verGroup = true;
    /** Virtuals (não existem na tabela) */
    public $password;
    public $password_confirm;
    const SCENARIO_PROFILE = 'profile';

    public static function tableName()
    {
        return 'users';
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ]);
    }
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_PROFILE] = ['file_id', 'theme', 'email', 'language_id', 'fullname', 'cpf_cnpj', 'phone', 'password', 'password_confirm'];
        return $scenarios;
    }

    public function rules()
    {
        return [
            [['group_id', 'password_reset_token'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['group_id', 'status'], 'integer'],

            [['email'], 'required', 'on' => ['create', 'update']],

            [['token_validate', 'created_at', 'updated_at'], 'safe'],
            [['username'], 'string', 'max' => 64],
            [['email', 'password_reset_token'], 'string', 'max' => 190],
            [['password_hash'], 'string', 'max' => 255],
            [['auth_key', 'access_token'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],

            // Regras de senha por cenário
            [['password', 'password_confirm'], 'required', 'on' => 'create'],
            [['password'], 'string', 'min' => 6],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => Yii::t('app', 'Passwords do not match.')],

        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'group_id' => Yii::t('app', 'Group'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'password_confirm' => Yii::t('app', 'Confirm Password'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'auth_key' => Yii::t('app', 'Auth Key'),
            'access_token' => Yii::t('app', 'Access Token'),
            'token_validate' => Yii::t('app', 'Token Validate'),
            'password_reset_token' => Yii::t('app', 'Password Reset Token'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    public function getLogs()
    {
        return $this->hasMany(Log::class, ['user_id' => 'id']);
    }
    public function getNotifications()
    {
        return $this->hasMany(Notification::class, ['user_id' => 'id']);
    }
    public function getRoles()
    {
        return $this->hasMany(Role::class, ['user_id' => 'id']);
    }
    public function getProfile()
    {
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }
    public function getUsersGroups()
    {
        return $this->hasMany(UserGroup::class, ['user_id' => 'id']);
    }

    /**
     * Retorna true se o usuário for master (admin global), considerando:
     *  - group_id base do usuário (users.group_id)
     *  - vínculos na tabela user_groups
     */
    public static function isMasterByUserId(int $userId, ?int $baseGroupId, \yii\db\Connection $db): bool
    {
        $groupsTable = \croacworks\essentials\models\Group::tableName();
        $ugTable = class_exists(\croacworks\essentials\models\UsersGroup::class)
            ? \croacworks\essentials\models\UsersGroup::tableName()
            : '{{%user_groups}}';

        // 1) base group_id é master?
        if ($baseGroupId && $baseGroupId > 0) {
            $isBaseMaster = (new \yii\db\Query())
                ->from($groupsTable)
                ->where(['id' => (int)$baseGroupId, 'level' => 'master'])
                ->limit(1)
                ->exists($db);
            if ($isBaseMaster) return true;
        }

        // 2) existe algum vínculo em user_groups com grupo master?
        $isMemberMaster = (new \yii\db\Query())
            ->from("$ugTable ug")
            ->innerJoin("$groupsTable g", 'g.id = ug.group_id')
            ->where(['ug.user_id' => (int)$userId, 'g.level' => 'master'])
            ->limit(1)
            ->exists($db);

        return (bool)$isMemberMaster;
    }


    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->viaTable('user_groups', ['user_id' => 'id']);
    }

    public function getUserGroupsId(): array
    {
        // grupos vindos da tabela pivot user_groups (se existir relation getGroups())
        $ids = [];
        try {
            $ids = $this->getGroups()->select('id')->column();
        } catch (\Throwable $e) {
            // se não houver relation, não quebra; segue vazio
        }

        // inclui SEMPRE o group principal do usuário
        if (!empty($this->group_id)) {
            $ids[] = (int)$this->group_id;
        }

        // normaliza
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        // Expande descendentes (mantém tua API e compatibilidade)
        return \croacworks\essentials\models\Group::getAllDescendantIds($ids);
    }

    /**
     * Gera hash de senha somente se $this->password vier preenchida.
     * Mantém a senha atual no update quando os campos ficam vazios.
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Garantir chaves obrigatórias
        if (empty($this->auth_key)) {
            $this->auth_key = Yii::$app->security->generateRandomString(32);
        }
        if (empty($this->access_token)) {
            $this->access_token = Yii::$app->security->generateRandomString(32);
        }

        // Se o usuário informou uma nova senha, gerar hash
        if (!empty($this->password)) {
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        } else {
            // No create, exigir que password_hash exista (por segurança)
            if ($insert && empty($this->password_hash)) {
                // Falha explícita se tentou criar sem senha
                $this->addError('password', Yii::t('app', 'Password is required.'));
                return false;
            }
            // No update e senha vazia: não mexe em password_hash (mantém)
        }

        return true;
    }
}
