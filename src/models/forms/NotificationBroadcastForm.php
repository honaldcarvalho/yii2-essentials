<?php
namespace croacworks\essentials\models\forms;

use Yii;
use yii\base\Model;

class NotificationBroadcastForm extends Model
{
    /** @var string 'user'|'group'|'all'|'all_global' */
    public $recipient_mode = 'user';
    public $user_id;
    public $group_id;
    public $include_children = true;

    public $title;
    public $content;
    public $type = 'system';
    public $url;

    public $push_expo = false;
    public $expo_data_json;

    public function rules(): array
    {
        return [
            [['recipient_mode', 'title'], 'required'],
            ['recipient_mode', 'in', 'range' => ['user','group','all','all_global']],
            [['user_id','group_id'], 'integer'],
            ['include_children', 'boolean'],
            ['title', 'string', 'max' => 255],
            [['content','url','type','expo_data_json'], 'string'],
            ['type', 'default', 'value' => 'system'],
            ['push_expo', 'boolean'],

            // conditional
            ['user_id', 'required', 'when' => fn($m)=>$m->recipient_mode==='user',
                'whenClient'=>"function(){return document.querySelector('[name=\"NotificationBroadcastForm[recipient_mode]\"]:checked').value==='user'}"],
            ['group_id', 'required', 'when' => fn($m)=>$m->recipient_mode==='group',
                'whenClient'=>"function(){return document.querySelector('[name=\"NotificationBroadcastForm[recipient_mode]\"]:checked').value==='group'}"],

            // optional JSON validation
            ['expo_data_json', function($attr){
                if ($this->$attr === null || trim($this->$attr) === '') return;
                json_decode($this->$attr, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addError($attr, Yii::t('app','Invalid JSON.'));
                }
            }],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'recipient_mode'   => Yii::t('app','Recipient'),
            'user_id'          => Yii::t('app','User'),
            'group_id'         => Yii::t('app','Group'),
            'include_children' => Yii::t('app','Include subgroups'),
            'title'            => Yii::t('app','Title'),
            'content'          => Yii::t('app','Content'),
            'type'             => Yii::t('app','Type'),
            'url'              => Yii::t('app','URL'),
            'push_expo'        => Yii::t('app','Send push (Expo)'),
            'expo_data_json'   => Yii::t('app','Extra push data (JSON)'),
        ];
    }

    public function expoData(): array
    {
        if (!$this->expo_data_json || trim($this->expo_data_json) === '') return [];
        $arr = json_decode($this->expo_data_json, true);
        return is_array($arr) ? $arr : [];
    }
}
