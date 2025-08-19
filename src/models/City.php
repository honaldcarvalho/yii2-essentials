<?php

namespace weebz\yii2basics\models;

use Yii;

/**
 * This is the model class for table "cities".
 *
 * @property int $id
 * @property int|null $state_id
 * @property string $name
 * @property int|null $ibge
 * @property int|null $status
 *
 * @property State $state
 */
class City extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cities';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['state_id', 'ibge', 'status'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 250],
            [['state_id'], 'exist', 'skipOnError' => true, 'targetClass' => State::class, 'targetAttribute' => ['state_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'state_id' => Yii::t('app', 'State ID'),
            'name' => Yii::t('app', 'Name'),
            'ibge' => Yii::t('app', 'Ibge'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[State]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }
}
