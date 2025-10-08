<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "licenses".
 *
 * @property int $id
 * @property int $license_type_id
 * @property int $group_id
 * @property string|null $validate
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int $status
 *
 * @property Groups $group
 * @property LicenseType $licenseType
 */

class License extends ModelCommon
{
    public $verGroup = false;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'licenses';
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        \croacworks\essentials\services\TriggerService::runForModel($this);
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['license_type_id', 'group_id'], 'required','on'=>['create','update']],
            [['license_type_id', 'group_id', 'status'], 'integer'],
            [['validate', 'created_at', 'updated_at'], 'safe'],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [['license_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => LicenseType::class, 'targetAttribute' => ['license_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'license_type_id' => Yii::t('app', 'License Type ID'),
            'group_id' => Yii::t('app', 'Group ID'),
            'validate' => Yii::t('app', 'Validate'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'status' => Yii::t('app', 'Active'),
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
     * Gets query for [[LicenseType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLicenseType()
    {
        return $this->hasOne(LicenseType::class, ['id' => 'license_type_id']);
    }
}