<?php

namespace croacworks\essentials\models;

use Yii;
/**
 * This is the model class for table "notification_messages".
 *
 * @property int $id
 * @property string $model
 * @property int|null $model_id
 * @property string $model_field
 * @property string $description
 * @property string|null $type
 * @property string|null $message
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $send_email
 * @property int|null $status
 */

class NotificationMessage extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification_messages';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['model', 'description'], 'required'],
            [['model_id', 'send_email', 'status'], 'integer'],
            [['type', 'message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['model', 'description'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'model' => Yii::t('app', 'Model'),
            'model_id' => Yii::t('app', 'Model ID'),
            'description' => Yii::t('app', 'Description'),
            'type' => Yii::t('app', 'Type'),
            'message' => Yii::t('app', 'Message'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'send_email' => Yii::t('app', 'Send Email'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    public static function notify($recipientId, $recipientType, $messageId = null, $description = null, $sendEmail = false)
    {
        $notification = new Notification();
        $notification->recipient_id = $recipientId;
        $notification->recipient_type = $recipientType;
        $notification->notification_message_id = $messageId;
        $notification->description = $description;
        $notification->send_email = $sendEmail ? 1 : 0;
        $notification->status = 1;
        $notification->created_at = date('Y-m-d H:i:s');
        $notification->updated_at = date('Y-m-d H:i:s');

        if ($notification->save()) {
            // Envia e-mail se for solicitado
            if ($sendEmail) {
                $recipient = $notification->recipient;
                $email = $recipient->email ?? null;

                if ($email) {
                    Yii::$app->mailer->compose()
                        ->setTo($email)
                        ->setSubject('Nova Notificação')
                        ->setTextBody($notification->description)
                        ->send();
                }
            }
            return true;
        }

        Yii::error($notification->errors, 'notification');
        return false;
    }
}
