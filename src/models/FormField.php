<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "form_fields".
 *
 * @property int $id
 * @property int $dynamic_form_id
 * @property string $label
 * @property string $name
 * @property int $type
 * @property string|null $default
 * @property string|null $items
 * @property string|null $model_class
 * @property string|null $model_field
 * @property string|null $model_criteria
 * @property string|null $sql
 * @property string|null $script
 * @property int $order
 * @property int|null $show
 * @property int|null $status
 *
 * @property FormResponse[] $formResponses
 * @property DynamicForm $template
 */
class FormField extends \croacworks\essentials\models\ModelCommon
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
        return 'form_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['default', 'model_class', 'model_field'], 'default', 'value' => null],
            [['status'], 'default', 'value' => 1,'on' => ['create', 'update']],
            [['dynamic_form_id', 'label', 'name', 'type', 'order'], 'required','on' => ['create', 'update']],
            [['dynamic_form_id', 'type', 'order', 'status'], 'integer'],
            [['label', 'name', 'default', 'model_class', 'model_field','script'], 'string', 'max' => 255],
            [['dynamic_form_id'], 'exist', 'skipOnError' => true, 'targetClass' => DynamicForm::class, 'targetAttribute' => ['dynamic_form_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'dynamic_form_id' => Yii::t('app', 'Template ID'),
            'label' => Yii::t('app', 'Label'),
            'name' => Yii::t('app', 'Name'),
            'type' => Yii::t('app', 'Type'),
            'default' => Yii::t('app', 'Default'),
            'model_class' => Yii::t('app', 'Model Class'),
            'model_field' => Yii::t('app', 'Model Field'),
            'order' => Yii::t('app', 'Order'),
            'show' => Yii::t('app', 'Show'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[FormResponses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormResponses()
    {
        return $this->hasMany(FormResponse::class, ['form_field_id' => 'id']);
    }

}
