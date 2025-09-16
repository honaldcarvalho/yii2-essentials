<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "files".
 *
 * @property int $id
 * @property int|null $group_id
 * @property int|null $folder_id
 * @property string $name
 * @property string|null $description
 * @property string $path
 * @property string $url
 * @property string|null $pathThumb
 * @property string|null $urlThumb
 * @property string $extension
 * @property string $type
 * @property int $size
 * @property int $duration
 * @property int $caption
 * @property int $status
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Folders $folder
 * @property Group $group
 * @property PageFiles[] $pageFiles
 * @property Params[] $params
 */
class File extends ModelCommon
{
    public $file;
    public $preview;
    public $extensions = [];
    public $max_size = 100;
    public $verGroup = true;

    // public function __construct() {
    //     parent::__construct();
    //     $this->verGroup = Yii::$app->params['upload.group'];
    // }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'files';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['update'] = ['folder_id','description','caption'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['folder_id', 'size','duration','caption','status'], 'integer'],
            [['name', 'path', 'url', 'extension', 'size'],'required','on'=>self::SCENARIO_DEFAULT],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'path', 'url', 'pathThumb', 'urlThumb'], 'string', 'max' => 300],
            [['description'], 'string', 'max' => 255],
            [['extension','type'], 'string', 'max' => 6],
            
            ['file', 'file', 'skipOnEmpty' => true,'maxSize' => 1024 * 1024 * $this->max_size, 'extensions' => $this->extensions,'when'=> function($model){
                return $model->file !== 'ajax' && $model->isNewRecord;
            }],
                    
            // ['file', 'required','when'=> function($model){
            //     return $model->isNewRecord;
            // }],
                    
            [['folder_id'], 'exist', 'skipOnError' => true, 'targetClass' => Folder::class, 'targetAttribute' => ['folder_id' => 'id']],

            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'group_id' => Yii::t('app', 'Group'),
            'folder_id' => Yii::t('app', 'Folder'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'path' => Yii::t('app', 'Path'),
            'url' => Yii::t('app', 'Url'),
            'pathThumb' => Yii::t('app', 'Path Thumb'),
            'urlThumb' => Yii::t('app', 'Url Thumb'),
            'extension' => Yii::t('app', 'Extension'),
            'type' => Yii::t('app', 'Type'),
            'size' => Yii::t('app', 'Size'),
            'duration' => Yii::t('app', 'Duration'),
            'status' => Yii::t('app', 'Active'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Group]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * Gets query for [[Folder]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFolder()
    {
        return $this->hasOne(Folder::class, ['id' => 'folder_id']);
    }

    /**
     * Gets query for [[Params]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParams()
    {
        return $this->hasMany(Configuration::class, ['file_id' => 'id']);
    }
}
