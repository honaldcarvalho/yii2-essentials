<?php

namespace croacworks\essentials\enums;

use Yii;

class QueueEntryLog extends yii\db\ActiveRecord
{
    const ACTION_CREATED     = 'created';
    const ACTION_CALLED      = 'called';
    const ACTION_RECALLED    = 'recalled';
    const ACTION_SKIPPED     = 'skipped';
    const ACTION_CANCELED    = 'canceled';
    const ACTION_FINISHED    = 'finished';
    const ACTION_TRANSFERRED = 'transferred';
    
    public static $list = [];

    public static function getList()
    {
        if (empty(self::$list)) {
            foreach (self::actionLabels() as $id => $name) {
                self::$list[] = ['id' => $id, 'name' => $name];
            }
        }

        return self::$list;
    }
    
    public static function actionLabels()
    {
        return [
            self::ACTION_CREATED     => Yii::t('app', 'Created'),
            self::ACTION_CALLED      => Yii::t('app', 'Called'),
            self::ACTION_RECALLED    => Yii::t('app', 'Recalled'),
            self::ACTION_SKIPPED     => Yii::t('app', 'Skipped'),
            self::ACTION_CANCELED    => Yii::t('app', 'Canceled'),
            self::ACTION_FINISHED    => Yii::t('app', 'Finished'),
            self::ACTION_TRANSFERRED => Yii::t('app', 'Transferred'),
        ];
    }

    public function getActionLabel()
    {
        return static::actionLabels()[$this->action] ?? Yii::t('app', 'Unknown');
    }
}