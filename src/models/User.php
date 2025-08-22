<?php

namespace croacworks\essentials\models;

use Yii;

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
 * @property int $created_at
 * @property int $updated_at
 * @property int $status
 *
 * @property Log[] $logs
 * @property Notification[] $notifications
 * @property Role[] $roles
 * @property UserProfile $profile
 * @property UsersGroup[] $usersGroups
 */

class User extends Account
{
    /** Virtuals (não existem na tabela) */
    public $password;
    public $password_confirm;

    public static function tableName()
    {
        return 'users';
    }

    public function rules()
    {
        return [
            [['group_id', 'password_reset_token'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['group_id','created_at', 'updated_at', 'status'], 'integer'],

            // IMPORTANTE: não exigir password_hash diretamente
            // Remover 'password_hash' do required aqui:
            [['username', 'email', 'auth_key', 'access_token', 'created_at', 'updated_at'], 'required'],

            [['token_validate'], 'safe'],
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
            'group_id' => Yii::t('app', 'Group ID'),
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

    public function getLogs()      { return $this->hasMany(Log::class, ['user_id' => 'id']); }
    public function getNotifications(){ return $this->hasMany(Notification::class, ['user_id' => 'id']); }
    public function getRoles()     { return $this->hasMany(Role::class, ['user_id' => 'id']); }
    public function getProfile()   { return $this->hasOne(UserProfile::class, ['user_id' => 'id']); }
    public function getUsersGroups(){ return $this->hasMany(UserGroup::class, ['user_id' => 'id']); }

    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->viaTable('users_groups', ['user_id' => 'id']);
    }

    public function getUserGroupsId()
    {
        $groupIds = $this->getGroups()->select('id')->column();
        return Group::getAllDescendantIds($groupIds);
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
