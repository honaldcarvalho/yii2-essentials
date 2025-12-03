<?php

namespace croacworks\essentials\models;

use Yii;
use yii\helpers\Json;
use yii\db\Expression;
use yii\data\ActiveDataProvider;
use croacworks\essentials\helpers\GeminiHelper;
use croacworks\essentials\helpers\TranslatorHelper;
use croacworks\essentials\models\DynamicForm;

/**
 * This is the model class for table "form_responses".
 *
 * @property int $id
 * @property int $dynamic_form_id
 * @property array|string $response_data
 * @property int|null $group_id
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DynamicForm $form
 */
class FormResponse extends \croacworks\essentials\models\ModelCommon
{
    /**
     * Stores dynamic attributes from JSON in memory.
     */
    protected $_dynamicAttributes = [];

    public static function tableName()
    {
        return 'form_responses';
    }

    public function rules()
    {
        return [
            [['dynamic_form_id'], 'required'],
            [['dynamic_form_id', 'group_id'], 'integer'],
            // response_data can be string (raw JSON) or array (decoded)
            [['response_data'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
            [['dynamic_form_id'], 'exist', 'skipOnError' => true, 'targetClass' => DynamicForm::class, 'targetAttribute' => ['dynamic_form_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'dynamic_form_id' => Yii::t('app', 'Form'),
            'response_data' => Yii::t('app', 'Response Data'),
            'group_id' => Yii::t('app', 'Group'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function getForm()
    {
        return $this->hasOne(DynamicForm::class, ['id' => 'dynamic_form_id']);
    }

    // -------------------------------------------------------------------------
    //  Magic Methods & Attribute Handling
    // -------------------------------------------------------------------------

    public function afterFind()
    {
        parent::afterFind();

        // Ensures response_data is an array and populates _dynamicAttributes
        if (is_string($this->response_data)) {
            try {
                $this->response_data = Json::decode($this->response_data);
            } catch (\Exception $e) {
                $this->response_data = [];
            }
        }

        if (is_array($this->response_data)) {
            $this->_dynamicAttributes = $this->response_data;
        } else {
            $this->_dynamicAttributes = [];
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Sets default group_id if available and empty
        if ($this->hasAttribute('group_id') && empty($this->group_id) && !Yii::$app->user->isGuest) {
            $this->group_id = (int)(Yii::$app->user->identity->group_id ?? 1);
        }

        // Syncs dynamic attributes back to response_data and encodes to JSON
        $this->response_data = Json::encode($this->_dynamicAttributes);

        return true;
    }

    /**
     * Retrieves the value of a field, used by FormResponseFieldColumn.
     * * @param string $attribute
     * @return mixed
     */
    public function getFieldValue($attribute)
    {
        // Utilizes the __get magic method to retrieve from _dynamicAttributes or standard attributes
        return $this->$attribute;
    }

    public function __get($name)
    {
        if (parent::hasAttribute($name) || parent::canGetProperty($name)) {
            return parent::__get($name);
        }
        return $this->_dynamicAttributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        if (parent::hasAttribute($name) || parent::canSetProperty($name)) {
            parent::__set($name, $value);
        } else {
            $this->_dynamicAttributes[$name] = $value;
            // Keeps response_data synced in real-time
            $this->response_data = $this->_dynamicAttributes;
        }
    }

    public function __isset($name)
    {
        return parent::__isset($name) || isset($this->_dynamicAttributes[$name]);
    }

    /**
     * Returns data as associative array (Legacy support).
     */
    public function getData()
    {
        return $this->_dynamicAttributes;
    }

    // -------------------------------------------------------------------------
    //  Search & Translation Features
    // -------------------------------------------------------------------------

    /**
     * Search implementation handling standard columns and JSON data.
     * @param array $params
     * @param array $config Options like pageSize, orderBy
     * @return ActiveDataProvider
     */
    public function search($params, $config = [])
    {
        $query = self::find();

        $dataProvider = new ActiveDataProvider(array_merge([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ], $config));

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // 1. Standard Column Filtering
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['dynamic_form_id' => $this->dynamic_form_id]);
        if ($this->hasAttribute('group_id')) {
            $query->andFilterWhere(['group_id' => $this->group_id]);
        }

        // 2. Dynamic Attributes Filtering (JSON)
        // Iterates over what was loaded into _dynamicAttributes via load()
        foreach ($this->_dynamicAttributes as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // MySQL/MariaDB syntax
            $expression = new Expression("LOWER(JSON_UNQUOTE(JSON_EXTRACT(response_data, '$.\"{$key}\"')))");
            $query->andFilterWhere(['like', $expression, mb_strtolower($value, 'UTF-8')]);
        }

        return $dataProvider;
    }

    /**
     * Translates all string content within response_data.
     * @param string $targetLanguage Target language code (e.g., 'en', 'es').
     * @param string $provider Translation provider ('google' or 'gemini').
     * @return bool Returns true if translation was successful for at least one field.
     */
    public function translateContent($targetLanguage, $provider = 'google')
    {
        $hasChanges = false;

        foreach ($this->_dynamicAttributes as $key => $value) {
            // Skips non-string values or empty strings
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            try {
                $translatedText = null;

                if ($provider === 'gemini') {
                    $instruction = Yii::t('app', 'Translate the following text to {0}. Preserve original formatting.', [$targetLanguage]);
                    $raw = GeminiHelper::processRequest($instruction, $value, 0.1);
                    $translatedText = GeminiHelper::cleanMarkdown($raw);
                } else {
                    $translatedText = TranslatorHelper::translate($value, $targetLanguage);
                }

                if ($translatedText && $translatedText !== $value) {
                    $this->_dynamicAttributes[$key] = $translatedText;
                    $hasChanges = true;
                }
            } catch (\Exception $e) {
                Yii::error("Translation failed for $key: " . $e->getMessage(), __METHOD__);
            }
        }

        if ($hasChanges) {
            $this->response_data = $this->_dynamicAttributes;
        }

        return $hasChanges;
    }

    // -------------------------------------------------------------------------
    //  Static Helper Logic (Fat Model)
    // -------------------------------------------------------------------------

    /**
     * Finds a FormResponse based on a specific field inside the JSON response_data.
     */
    public static function findByJsonField(string $field, $value, ?int $dynamicFormId = null): ?FormResponse
    {
        $query = self::find();

        if ($dynamicFormId) {
            $query->andWhere(['dynamic_form_id' => $dynamicFormId]);
        }

        // JSON search query
        $query->andWhere([
            '=',
            new Expression("JSON_UNQUOTE(JSON_EXTRACT(response_data, '$.\"{$field}\"'))"),
            $value
        ]);

        return $query->one();
    }

    /**
     * Finds a FormResponse based on page_id (specific usage).
     */
    public static function findByPageId(int $formId, int $pageId): ?FormResponse
    {
        return self::findByJsonField('page_id', (string)$pageId, $formId);
    }

    /**
     * Ensures a FormResponse exists for the given page.
     */
    public static function ensureForPage(int $formId, int $pageId): FormResponse
    {
        $model = self::findByPageId($formId, $pageId);

        if (!$model) {
            $model = new self([
                'dynamic_form_id' => $formId,
                'response_data'   => ['page_id' => $pageId],
            ]);

            if (!$model->save(false)) {
                Yii::error("Failed to create FormResponse for Page ID: $pageId", __METHOD__);
            }
            $model->refresh();
        }

        return $model;
    }

    /**
     * Deletes all FormResponses associated with a specific page ID.
     */
    public static function deleteAllByPageId(int $formId, int $pageId): void
    {
        $responses = self::find()
            ->where(['dynamic_form_id' => $formId])
            ->andWhere([
                '=',
                new Expression("JSON_UNQUOTE(JSON_EXTRACT(response_data, '$.page_id'))"),
                (string)$pageId
            ])
            ->all();

        foreach ($responses as $response) {
            $response->delete();
        }
    }
}
