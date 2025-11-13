<?php

namespace croacworks\essentials\models;

use croacworks\essentials\behaviors\AttachFileBehavior;
use croacworks\essentials\models\Language;
use Yii;
use yii\helpers\Inflector;

/**
 * Model for table "pages".
 *
 * @property int $id
 * @property int|null $group_id
 * @property int|null $model_group_id
 * @property int|null $page_section_id
 * @property string $language_id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string|null $content
 * @property string|null $keywords
 * @property string|null $custom_css
 * @property string|null $custom_js
 * @property datetime|null $created_at
 * @property datetime|null $updated_at
 * @property int|null $list
 * @property int|null $status
 *
 * @property PageFiles[] $pageFiles
 * @property PageSection $pageSection
 * @property Group $group
 * @property File $file
 */
class Page extends ModelCommon
{
    /** Enable default group scoping from ModelCommon */
    public $verGroup = true;

    private $_oldSlug;

    /** Selected tag IDs (form helper) */
    public $tagIds = [];

    public static function tableName()
    {
        return 'pages';
    }

    public function rules()
    {
        return [
            [['page_section_id', 'list', 'status'], 'integer'],
            [['title'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['content', 'keywords', 'custom_js', 'custom_css', 'language_id'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 300],

            [['page_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => PageSection::class, 'targetAttribute' => ['page_section_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],

            [
                ['slug', 'group_id', 'language_id', 'page_section_id'],
                'unique',
                'targetAttribute' => ['slug', 'group_id', 'language_id', 'page_section_id'],
                'message' => Yii::t('app', 'A page with this Slug/Group/Language/Section already exists.')
            ],

            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
        ];
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => AttachFileBehavior::class,
                'attribute' => 'file_id',
                'removeFlagParam' => 'remove',
                'deleteOldOnReplace' => true,
                'deleteOnOwnerDelete' => false,
                'debug' => true, // keep enabled while stabilizing
            ],
        ]);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->_oldSlug = $this->slug;
        // Preload tagIds for form usage
        $this->tagIds = $this->getTags()->select('id')->column();
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        // Default group id for new records
        if ($this->isNewRecord && empty($this->model_group_id)) {
            $this->model_group_id = 0;
        }

        $oldSlug  = $this->_oldSlug ?? null;
        $oldTitle = $this->getOldAttribute('title');
        $slugEmpty = empty($this->slug);
        $titleChanged = $oldTitle !== $this->title;
        $slugChanged  = $oldSlug !== $this->slug;

        // ===== Slug logic =====
        if ($this->isNewRecord) {
            // New record: always generate if empty
            if ($slugEmpty) {
                $this->slug = $this->generateUniqueSlug($this->title);
            } else {
                $this->slug = $this->generateUniqueSlug($this->slug);
            }
        } else {
            // Update: regenerate if slug empty or title changed (and slug wasn't edited manually)
            if ($slugEmpty || ($titleChanged && !$slugChanged)) {
                $base = $this->title ?: $this->slug ?: (string)$this->id;
                $this->slug = $this->generateUniqueSlug($base);
            } elseif ($slugChanged) {
                // If slug was manually edited, still ensure uniqueness
                $this->slug = $this->generateUniqueSlug($this->slug);
            }
        }

        return true;
    }

    /** True if this record is the owner of its model group */
    public function getIsGroupOwner(): bool
    {
        return (int)$this->id === (int)$this->model_group_id;
    }

    /** Generate a unique slug within the current section scope */
    public function generateUniqueSlug(?string $baseTitle = null): string
    {
        $base = trim((string)($baseTitle ?? $this->title ?? $this->description ?? $this->id));
        $seed = Inflector::slug($base);
        if ($seed === '') {
            $seed = (string)$this->id; // hard fallback
        }

        $try = $seed;
        $i = 2;

        while (static::find()
            ->andWhere(['page_section_id' => $this->page_section_id, 'slug' => $try])
            ->andFilterWhere(['<>', 'id', $this->id])
            ->exists()
        ) {
            $try = $seed . '-' . $i++;
        }
        return $try;
    }

    /** Sync pivot and group id after save */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Set model_group_id to self on first insert when it was 0 (cloneTotal starts a new group)
        if ($insert && (int)$this->model_group_id === 0) {
            static::updateAll(['model_group_id' => $this->id], ['id' => $this->id]);
            $this->model_group_id = $this->id;
        }

        // Normalize tag inputs (IDs or names) and upsert tags
        $resolvedIds = [];
        foreach ((array)$this->tagIds as $val) {
            $val = is_string($val) ? trim($val) : $val;
            if ($val === '' || $val === null) {
                continue;
            }

            if (ctype_digit((string)$val)) {
                $resolvedIds[] = (int)$val;
                continue;
            }

            $name = $val;
            $slug = Inflector::slug($name);

            $tag = Tag::find()->where(['slug' => $slug])->one();
            if ($tag === null) {
                $tag = new Tag([
                    'name'   => $name,
                    'slug'   => $slug,
                    'status' => 1,
                ]);
                if (!$tag->save()) {
                    Yii::error(['tag_create_failed' => $tag->errors, 'name' => $name], __METHOD__);
                    continue;
                }
            }
            $resolvedIds[] = (int)$tag->id;
        }

        // Sync pivot table
        $resolvedIds = array_values(array_unique(array_map('intval', $resolvedIds)));
        $currentIds  = $this->getTags()->select('id')->column();

        $toAdd = array_diff($resolvedIds, $currentIds);
        $toDel = array_diff($currentIds, $resolvedIds);

        if ($toDel) {
            Yii::$app->db->createCommand()
                ->delete('{{%page_tags}}', ['page_id' => $this->id, 'tag_id' => $toDel])
                ->execute();
        }
        foreach ($toAdd as $id) {
            Yii::$app->db->createCommand()
                ->insert('{{%page_tags}}', ['page_id' => $this->id, 'tag_id' => $id])
                ->execute();
        }
    }

    /** Relation: Group */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    public function attributeLabels()
    {
        return [
            'id'              => 'ID',
            'file_id'         => Yii::t('app', 'Cover'),
            'page_section_id' => Yii::t('app', 'Page Section'),
            'model_group_id'  => Yii::t('app', 'Model Group'),
            'slug'            => Yii::t('app', 'Slug'),
            'language'        => Yii::t('app', 'Language'),
            'title'           => Yii::t('app', 'Title'),
            'description'     => Yii::t('app', 'Description'),
            'content'         => Yii::t('app', 'Content'),
            'custom_js'       => Yii::t('app', 'Custom JavaScript'),
            'custom_css'      => Yii::t('app', 'Custom CSS'),
            'keywords'        => Yii::t('app', 'Keywords'),
            'list'            => Yii::t('app', 'Show in lists'),
            'created_at'      => Yii::t('app', 'Created at'),
            'updated_at'      => Yii::t('app', 'Updated at'),
            'status'          => Yii::t('app', 'Active'),
        ];
    }

    /** Relation: Tags (via pivot) */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('{{%page_tags}}', ['page_id' => 'id']);
    }

    /** Relation: PageFiles (+ eager File) */
    public function getPageFiles()
    {
        return $this->hasMany(PageFile::class, ['page_id' => 'id'])
            ->inverseOf('page')
            ->with('file');
    }

    /** Relation: Files via PageFiles */
    public function getFiles()
    {
        return $this->hasMany(File::class, ['id' => 'file_id'])
            ->via('pageFiles');
    }

    /** Relation: PageSection */
    public function getPageSection()
    {
        return $this->hasOne(PageSection::class, ['id' => 'page_section_id'])->alias('pageSection');
    }

    /** Relation: Language */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }

    /** Relation: File (cover) */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }
}
