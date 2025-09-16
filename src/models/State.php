<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "states".
 *
 * @property int $id
 * @property int|null $country_id
 * @property string $name
 * @property string|null $uf
 * @property int|null $ibge
 * @property string|null $ddd
 * @property int|null $status
 *
 * @property Cities[] $cities
 * @property Country $country
 */
class State extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'states';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['country_id', 'ibge', 'status'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 250],
            [['uf'], 'string', 'max' => 2],
            [['ddd'], 'string', 'max' => 255],
            //[['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'country_id' => Yii::t('app', 'Country ID'),
            'name' => Yii::t('app', 'Name'),
            'uf' => Yii::t('app', 'Uf'),
            'ibge' => Yii::t('app', 'Ibge'),
            'ddd' => Yii::t('app', 'Ddd'),
            'status' => Yii::t('app', 'Active'),
        ];
    }

    /**
     * Gets query for [[Cities]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::class, ['state_id' => 'id']);
    }

    /**
     * Gets query for [[Country]].
     *
     * @return \yii\db\ActiveQuery
     */
    // public function getCountry()
    // {
    //     return $this->hasOne(Country::class, ['id' => 'country_id']);
    // }
}
