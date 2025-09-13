<?php

namespace croacworks\essentials\models;

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\ModelCommon;
use Yii;

/**
 * This is the model class for table "notifications".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $notification_message_id
 * @property string $description
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $send_email
 * @property int|null $status
 *
 * @property NotificationMessage $notificationMessage
 * @property User $user
 */
class Notification extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notifications';
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
            [['user_id', 'notification_message_id', 'send_email', 'status'], 'integer'],
            [['notification_message_id', 'description'], 'required','on'=> self::SCENARIO_DEFAULT],
            [['created_at', 'updated_at'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['notification_message_id'], 'exist', 'skipOnError' => true, 'targetClass' => NotificationMessage::class, 'targetAttribute' => ['notification_message_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User'),
            'notification_message_id' => Yii::t('app', 'Notification Message'),
            'description' => Yii::t('app', 'Description'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'send_email' => Yii::t('app', 'Send Email'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[NotificationMessage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotificationMessage()
    {
        return $this->hasOne(NotificationMessage::class, ['id' => 'notification_message_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function unreadCountForUser(?int $userId=null): int
    {
        $userId = $userId ?: (int)Yii::$app->user->id;
        $groups = AuthorizationController::getUserGroups();

        return (int)static::find()
            ->andWhere(['user_id' => $userId, 'is_read' => 0])
            ->andWhere(['group_id' => $groups])
            ->count();
    }
}
