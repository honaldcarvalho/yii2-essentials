<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "form_responses".
 *
 * @property int $id
 * @property int $dynamic_form_id
 * @property string $response_data
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DynamicForm $form
 */
class FormResponse extends \croacworks\essentials\models\ModelCommon
{
    public $verGroup;

    public static function tableName()
    {
        return 'form_responses';
    }

    public function rules()
    {
        return [
            [['dynamic_form_id', 'response_data'], 'required'],
            [['dynamic_form_id'], 'integer'],
            [['response_data'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
            [['dynamic_form_id'], 'exist', 'skipOnError' => true, 'targetClass' => DynamicForm::class, 'targetAttribute' => ['dynamic_form_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'dynamic_form_id' => Yii::t('app', 'Form'),
            'response_data' => Yii::t('app', 'Resposta'),
            'created_at' => Yii::t('app', 'Criado em'),
            'updated_at' => Yii::t('app', 'Atualizado em'),
        ];
    }

    public function getForm()
    {
        return $this->hasOne(DynamicForm::class, ['id' => 'dynamic_form_id']);
    }

    /**
     * Retorna os dados como array associativo (campo => valor).
     */
    public function getData()
    {
        return $this->response_data ?? [];
    }

    /**
     * Retorna o valor de um campo específico.
     */
    public function getFieldValue($fieldName)
    {
        $data = $this->getData();
        return $data[$fieldName] ?? null;
    }
}
