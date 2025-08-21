<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "meta_tags".
 *
 * @property int $id
 * @property int $configuration_id
 * @property string $name
 * @property string|null $description
 * @property string $content
 * @property int|null $status
 */
class MetaTag extends ModelCommon
{
    public $verGroup = false;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'meta_tags';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['configuration_id', 'name', 'content'], 'required'],
            [['configuration_id', 'status'], 'integer'],
            [['content'], 'string'],
            [['name', 'description'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'configuration_id' => Yii::t('app', 'Configuration ID'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

}
