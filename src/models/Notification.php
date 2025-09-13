<?php

namespace croacworks\essentials\models;

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\ModelCommon;
use Yii;

class Notification extends ModelCommon
{
    public $send_push = 0;
    public $send_email = 0;
    public $token;
    public const STATUS_UNREAD = 0;
    public const STATUS_READ   = 1;

    public static function tableName()
    {
        return 'notifications';
    }

    public function rules()
    {
        return [
            [['recipient_id', 'recipient_type'], 'required'],
            [['recipient_id', 'notification_message_id', 'send_email', 'status'], 'integer'],
            [['description'], 'string', 'max' => 255],
            [['content'], 'string'],
            [['recipient_type'], 'in', 'range' => ['user', 'patient']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'recipient_id' => Yii::t('app', 'Recipient'),
            'recipient_type' => Yii::t('app', 'Recipient Type'),
            'notification_message_id' => Yii::t('app', 'Notification Message'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'send_email' => Yii::t('app', 'Send Email'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getNotificationMessage()
    {
        return $this->hasOne(NotificationMessage::class, ['id' => 'notification_message_id']);
    }

    public function markAsRead(): bool
    {
        if ((int)$this->status !== self::STATUS_READ) {
            $this->status = self::STATUS_READ;
            $this->read_at = date('Y-m-d H:i:s');
            return $this->save(false, ['status','read_at']);
        }
        return true;
    }
}
