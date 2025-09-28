<?php

namespace croacworks\essentials\models;

class AccessLog extends  ModelCommon
{
    public $startDate;
    public $endDate;
    /**
     * {@inheritdoc}
     */
    public $verGroup = false;
    
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    public static function tableName()
    {
        return 'access_log';
    }

    public function rules()
    {
        return [
            [['ip_address', 'url'], 'required'],
            [['ip_address'], 'string', 'max' => 45],
            [['url'], 'string'],
            [['created_at'], 'safe'],
        ];
    }
}
