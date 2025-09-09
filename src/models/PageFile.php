<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "page_files".
 *
 * @property int $id
 * @property int $page_id
 * @property int $file_id
 *
 * @property Files $file
 * @property Pages $page
 */
class PageFile extends ModelCommon
{
    public $verGroup;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'page_files';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['page_id', 'file_id'], 'required'],
            [['page_id', 'file_id'], 'integer'],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            [['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => Page::class, 'targetAttribute' => ['page_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Page ID',
            'file_id' => 'File ID',
        ];
    }

    /**
     * Gets query for [[File]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    /**
     * Gets query for [[Page]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(Page::class, ['id' => 'page_id']);
    }

    public static function groupRelationPath()
    {
        return ['page'];
    }
}
