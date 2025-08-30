<?php

namespace croacworks\essentials\models;

use croacworks\essentials\models\ModelCommon;
use Yii;

/**
 * This is the model class for table "roles".
 *
 * @property int $id
 * @property int|null $group_id
 * @property int|null $user_id
 * @property string|null $name
 * @property string $controller
 * @property string $action
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Group $group
 * @property User $user
 */
class Role extends ModelCommon
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'roles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id', 'user_id', 'name'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['group_id', 'user_id', 'status'], 'integer'],
            [['controller', 'actions', 'created_at', 'updated_at'], 'required'],
            [['name'], 'string', 'max' => 120],
            [['controller'], 'string', 'max' => 255],
            [['actions'], 'string', 'max' => 64],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'user_id' => Yii::t('app', 'User ID'),
            'name' => Yii::t('app', 'Name'),
            'controller' => Yii::t('app', 'Controller'),
            'actions' => Yii::t('app', 'actions'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Group]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
