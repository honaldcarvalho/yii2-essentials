<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "license_types".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $value
 * @property string $contract
 * @property int $max_devices
 * @property int $status
 *
 * @property Licenses[] $licenses
 */
class LicenseType extends ModelCommon
{
    
    public $verGroup = false;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'license_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'description', 'value', 'contract'], 'required','on'=>['create','update']],
            [['description', 'contract'], 'string'],
            [['max_devices', 'status'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['value'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'value' => Yii::t('app', 'Value'),
            'contract' => Yii::t('app', 'Contract'),
            'max_devices' => Yii::t('app', 'Max Devices'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[Licenses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLicenses()
    {
        return $this->hasMany(License::class, ['license_type_id' => 'id']);
    }
}
