<?php

namespace croacworks\essentials\enums;

use Yii;

class FormFieldType extends yii\db\ActiveRecord
{
    const TYPE_HIDDEN      = 0;
    const TYPE_TEXT        = 1;
    const TYPE_NUMBER      = 2;
    const TYPE_TEXTAREA    = 3;
    const TYPE_DATE        = 4;
    const TYPE_SELECT      = 5;
    const TYPE_MULTIPLE    = 6;
    const TYPE_CHECKBOX    = 7;
    const TYPE_EMAIL       = 8;
    const TYPE_PHONE       = 9;
    const TYPE_IDENTIFIER  = 10;
    const TYPE_MODEL       = 11;
    const TYPE_SQL         = 12;
    const TYPE_DATETIME    = 13;
    const TYPE_FILE        = 14;

    public static $list = [];

    public static function getList()
    {
        if (empty(self::$list)) {
            foreach (self::typeLabels() as $id => $name) {
                self::$list[] = ['id' => $id, 'name' => $name];
            }
        }

        return self::$list;
    }

    public static function typeLabels()
    {
        return [
            self::TYPE_TEXT       => Yii::t('app', 'Text'),
            self::TYPE_HIDDEN     => Yii::t('app', 'Hidden'),
            self::TYPE_NUMBER     => Yii::t('app', 'Number'),
            self::TYPE_TEXTAREA   => Yii::t('app', 'Textarea'),
            self::TYPE_DATE       => Yii::t('app', 'Date'),
            self::TYPE_DATETIME   => Yii::t('app', 'Date/Time'),
            self::TYPE_SELECT     => Yii::t('app', 'Select'),
            self::TYPE_MULTIPLE   => Yii::t('app', 'Multiple Select'),
            self::TYPE_CHECKBOX   => Yii::t('app', 'Checkbox'),
            self::TYPE_EMAIL      => Yii::t('app', 'Email'),
            self::TYPE_PHONE      => Yii::t('app', 'Phone'),
            self::TYPE_IDENTIFIER => Yii::t('app', 'Identifier'),
            self::TYPE_MODEL      => Yii::t('app', 'Model'),
            self::TYPE_SQL        => Yii::t('app', 'Script SQL'),
            self::TYPE_FILE       => Yii::t('app', 'File'),
        ];
    }

    public function getTypeLabel()
    {
        return static::typeLabels()[$this->type] ?? Yii::t('app', 'Unknown');
    }
}
