<?php

namespace app\models;

use croacworks\essentials\models\ModelCommon;
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
 * @property string|null $password_reset_token
 * @property int $created_at
 * @property int $updated_at
 * @property int $status
 *
 * @property Logs[] $logs
 * @property UsersGroups[] $usersGroups
 */
class User extends ModelCommon
{


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
            [['group_id', 'created_at', 'updated_at', 'status'], 'integer'],
            [['username', 'email', 'password_hash', 'auth_key', 'created_at', 'updated_at'], 'required'],
            [['username'], 'string', 'max' => 64],
            [['email', 'password_reset_token'], 'string', 'max' => 190],
            [['password_hash'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],
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
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'auth_key' => Yii::t('app', 'Auth Key'),
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
        return $this->hasMany(Logs::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UsersGroups]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsersGroups()
    {
        return $this->hasMany(UsersGroups::class, ['user_id' => 'id']);
    }

}
