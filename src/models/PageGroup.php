<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * Page model (specialization of Page) discriminated by PageSection.
 *
 * Guarantees:
 * - Always tied to the PageSection identified by SECTION_SLUG.
 * - Queries are automatically scoped to that section.
 */

class PageGroup extends Page
{
    /** @var string Slug of the PageSection representing pages */
    public const SECTION_SLUG = 'page';
    public const hasDynamic = false;

    /** @var int|null Cached resolved section id */
    private static ?int $_sectionId = null;

    /**
     * Resolve and cache the PageSection id by slug.
     * Throws a clear exception if not found (prevents silent misclassification).
     */
    public static function sectionId(): int
    {
        if (static::$_sectionId !== null) {
            return static::$_sectionId;
        }

        $section = PageSection::find()
            ->select('id')
            ->andWhere(['slug' => static::SECTION_SLUG])
            ->andWhere(['status' => 1])
            ->one();

        if (!$section) {
            throw new \RuntimeException(
                "PageSection with slug '" . static::SECTION_SLUG . "' not found or inactive. " .
                    "Create it first (page_sections.slug='" . static::SECTION_SLUG . "')."
            );
        }

        static::$_sectionId = (int)$section->id;
        return static::$_sectionId;
    }

    /**
     * Auto-assign the Page section on new records.
     */
    public function init(): void
    {
        parent::init();
        if ($this->isNewRecord && empty($this->page_section_id)) {
            $this->page_section_id = static::sectionId();
        }
    }

    /**
     * Ensure the correct section before validation (covers updates/imports).
     */
    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if (empty($this->page_section_id)) {
            $this->page_section_id = static::sectionId();
        }
        return true;
    }

    /**
     * Prevent changing this model to another section by mistake.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                'page_section_id',
                'compare',
                'compareValue' => static::sectionId(),
                'message' => Yii::t('app', 'This record must remain in the Page section.')
            ],
        ]);
    }

    /**
     * Scope all queries to the Page section.
     */
    public static function find($verGroup = null)
    {
        $query = parent::find();
        // Narrow results to the page section
        return $query->andWhere([static::tableName() . '.page_section_id' => static::sectionId()]);
    }

    /**
     * Optional: friendlier label for the locked field.
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['page_section_id'] = Yii::t('app', 'Section (fixed: Page)');
        return $labels;
    }
}
