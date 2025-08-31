<?php

namespace croacworks\essentials\models;

use croacworks\essentials\models\ModelCommon;

use Yii;

/**
 * This is the model class for table "roles_templates".
 *
 * @property int $id
 * @property string $level
 * @property string $controller
 * @property string|null $actions
 * @property string $origin
 * @property int $status
 */
class RoleTemplate extends ModelCommon
{

    /**
     * ENUM field values
     */
    const LEVEL_MASTER = 'master';
    const LEVEL_ADMIN = 'admin';
    const LEVEL_USER = 'user';
    const LEVEL_FREE = 'free';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'roles_templates';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['actions'], 'default', 'value' => null],
            [['origin'], 'default', 'value' => '*'],
            [['status'], 'default', 'value' => 1],
            [['level', 'controller'], 'required','on'=>['create','update']],
            [['level', 'actions'], 'string'],
            [['status'], 'integer'],
            [['controller'], 'string', 'max' => 255],
            [['origin'], 'string', 'max' => 50],
            ['level', 'in', 'range' => array_keys(self::optsLevel())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'level' => Yii::t('app', 'Level'),
            'controller' => Yii::t('app', 'Controller'),
            'actions' => Yii::t('app', 'Actions'),
            'origin' => Yii::t('app', 'Origin'),
            'status' => Yii::t('app', 'Status'),
        ];
    }


    /**
     * column level ENUM value labels
     * @return string[]
     */
    public static function optsLevel()
    {
        return [
            self::LEVEL_MASTER => Yii::t('app', 'master'),
            self::LEVEL_ADMIN => Yii::t('app', 'admin'),
            self::LEVEL_USER => Yii::t('app', 'user'),
            self::LEVEL_FREE => Yii::t('app', 'free'),
        ];
    }

    /**
     * @return string
     */
    public function displayLevel()
    {
        return self::optsLevel()[$this->level];
    }

    /**
     * @return bool
     */
    public function isLevelMaster()
    {
        return $this->level === self::LEVEL_MASTER;
    }

    public function setLevelToMaster()
    {
        $this->level = self::LEVEL_MASTER;
    }

    /**
     * @return bool
     */
    public function isLevelAdmin()
    {
        return $this->level === self::LEVEL_ADMIN;
    }

    public function setLevelToAdmin()
    {
        $this->level = self::LEVEL_ADMIN;
    }

    /**
     * @return bool
     */
    public function isLevelUser()
    {
        return $this->level === self::LEVEL_USER;
    }

    public function setLevelToUser()
    {
        $this->level = self::LEVEL_USER;
    }

    /**
     * @return bool
     */
    public function isLevelFree()
    {
        return $this->level === self::LEVEL_FREE;
    }

    public function setLevelToFree()
    {
        $this->level = self::LEVEL_FREE;
    }
}
