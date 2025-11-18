<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "link_models".
 *
 * @property int $id
 * @property int|null $model_id
 * @property string $model
 * @property int|null $parent_id
 * @property string $parent_model
 * @property int|null $status
 */
class LinkModel extends ModelCommon
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'link_models';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['model_id', 'parent_id'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1],
            [['model_id', 'parent_id', 'status'], 'integer'],
            [['model', 'parent_model'], 'required'],
            [['model', 'parent_model'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'model_id' => Yii::t('app', 'Model ID'),
            'model' => Yii::t('app', 'Model'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'parent_model' => Yii::t('app', 'Parent Model'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

}
