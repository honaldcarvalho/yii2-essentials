<?php

namespace croacworks\essentials\enums;
use Yii;

class QueueEntry extends yii\db\ActiveRecord
{
    const STATUS_WAITING     = 0;
    const STATUS_CALLED      = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_FINISHED    = 3;
    const STATUS_CANCELED    = 4;
    const STATUS_SKIPPED     = 5;
    const STATUS_RECALLED    = 6;
    const STATUS_TRANSFERRED = 7;
    
    public static $list = [];
    
    public static function getList()
    {
        if (empty(self::$list)) {
            foreach (self::statusLabels() as $id => $name) {
                self::$list[] = ['id' => $id, 'name' => $name];
            }
        }

        return self::$list;
    }

    public static function statusLabels()
    {
        return [
            self::STATUS_WAITING     => Yii::t('app', 'Waiting'),
            self::STATUS_CALLED      => Yii::t('app', 'Called'),
            self::STATUS_IN_PROGRESS => Yii::t('app', 'In progress'),
            self::STATUS_FINISHED    => Yii::t('app', 'Finished'),
            self::STATUS_CANCELED    => Yii::t('app', 'Canceled'),
            self::STATUS_SKIPPED     => Yii::t('app', 'Skipped'),
            self::STATUS_RECALLED    => Yii::t('app', 'Recalled'),
            self::STATUS_TRANSFERRED => Yii::t('app', 'Transferred'),
        ];
    }

    public function getStatusLabel()
    {
        return static::statusLabels()[$this->status] ?? Yii::t('app', 'Unknown');
    }
}
