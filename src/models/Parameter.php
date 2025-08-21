<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "params".
 *
 * @property int $id
 * @property int $configuration_id
 * @property string|null $description
 * @property string $name
 * @property string $value
 * @property int|null $status

 * @property Configuration $configuration
 */
class Parameter extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parameters';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['configuration_id', 'name', 'value'], 'required','on'=>self::SCENARIO_DEFAULT],
            [['configuration_id', 'status'], 'integer'],
            [['value'], 'string'],
            [['description', 'name'], 'string', 'max' => 255],
            [['configuration_id'], 'exist', 'skipOnError' => true, 'targetClass' => Configuration::class, 'targetAttribute' => ['configuration_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('*', 'ID'),
            'configuration_id' => Yii::t('*', 'Configuration'),
            'description' => Yii::t('*', 'Description'),
            'name' => Yii::t('*', 'Name'),
            'value' => Yii::t('*', 'Value'),
            'status' => Yii::t('*', 'Status'),
        ];
    }

    /**
     * Gets query for [[Configuration]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConfiguration()
    {
        return $this->hasOne(Configuration::class, ['id' => 'configuration_id']);
    }

    public static function groupRelationPath()
    {
        return ['configuration'];
    }
}