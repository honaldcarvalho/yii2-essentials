<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property int|null $group_id
 * @property int|null $language_id
 * @property string|null $theme
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
    public $profile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id', 'password_reset_token'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['theme'], 'default', 'value' => 'light'],
            [['group_id', 'language_id', 'created_at', 'updated_at', 'status'], 'integer'],
            [['username', 'email', 'password_hash', 'auth_key', 'access_token', 'created_at', 'updated_at'], 'required'],
            [['token_validate'], 'safe'],
            [['theme'], 'string', 'max' => 10],
            [['username'], 'string', 'max' => 64],
            [['email', 'password_reset_token'], 'string', 'max' => 190],
            [['password_hash'], 'string', 'max' => 255],
            [['auth_key', 'access_token'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['profile'], 'exist', 'skipOnError' => true, 'targetClass' => UserProfile::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'group_id' => Yii::t('app', 'Group ID'),
            'language_id' => Yii::t('app', 'Language ID'),
            'theme' => Yii::t('app', 'Theme'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
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

    /**
     * Gets query for [[Logs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(Log::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Notifications]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotifications()
    {
        return $this->hasMany(Notification::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Roles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Role::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UserProfiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserProfile()
    {
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UsersGroups]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsersGroups()
    {
        return $this->hasMany(UserGroup::class, ['user_id' => 'id']);
    }

}
