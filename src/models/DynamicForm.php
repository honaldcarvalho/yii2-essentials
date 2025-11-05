<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "dynamic_forms".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $status
 *
 * @property FormField[] $formFields
 */
class DynamicForm extends \croacworks\essentials\models\ModelCommon
{

    public $verGroup;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dynamic_forms';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1,'on' => ['create', 'update']],
            [['name'], 'required','on' => ['create', 'update']],
            [['status'], 'integer'],
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
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[FormFields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormFields()
    {
        return $this->hasMany(FormField::class, ['dynamic_form_id' => 'id']);
    }
}
