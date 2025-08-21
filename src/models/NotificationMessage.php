<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "notification_messages".
 *
 * @property int $id
 * @property string $description
 * @property string|null $type
 * @property string|null $message
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $status
 *
 * @property Notifications[] $notifications
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
            [['description'], 'required','on'=> self::SCENARIO_DEFAULT],
            [['type', 'message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['status'], 'integer'],
            [['description'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'description' => Yii::t('app', 'Description'),
            'type' => Yii::t('app', 'Type'),
            'message' => Yii::t('app', 'Message'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[Notifications]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotifications()
    {
        return $this->hasMany(Notification::class, ['notification_message_id' => 'id']);
    }
}
