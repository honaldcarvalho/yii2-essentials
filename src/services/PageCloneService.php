<?php

namespace croacworks\essentials\services;

use Yii;
use croacworks\essentials\models\Page;
use croacworks\essentials\models\File;
use yii\db\Query;

class PageCloneService
{
    /**
     * Clones the File record (DB only), preserving the physical file.
     */
    private static function cloneCoverFile(int $fileId): ?int
    {
        $sourceFile = File::findOne($fileId);
        if (!$sourceFile) {
            return null;
        }

        $cloneFile = new File();
        $cloneFile->attributes = $sourceFile->attributes;
        $cloneFile->id = null;
        $cloneFile->isNewRecord = true;

        return $cloneFile->save(false) ? (int)$cloneFile->id : null;
    }

    /**
     * Internal helper to handle common save logic, overrides, and relations.
     */
    private static function saveCloneInternal(Page $clone, Page $source, array $overrides = []): Page
    {
        foreach ($overrides as $k => $v) {
            $clone->$k = $v;
        }

        // Handle cover file
        $newFileId = null;
        if ($source->file_id) {
            $newFileId = self::cloneCoverFile((int)$source->file_id);
            $clone->file_id = $newFileId;
        } else {
            $clone->file_id = null;
        }

        // Force slug regeneration
        $clone->slug = $clone->generateUniqueSlug(null);

        if (!$clone->save(false)) {
            throw new \RuntimeException(Yii::t('app', 'Failed to save clone.'));
        }

        // Force persist file_id (bypass AttachFileBehavior limitations during clone)
        if ($newFileId !== null) {
            Page::updateAll(['file_id' => $newFileId], ['id' => $clone->id]);
            $clone->file_id = $newFileId;
        }

        // Clone N:N PageFiles
        $rows = (new Query())
            ->from('{{%page_files}}')
            ->where(['page_id' => $source->id])
            ->all();

        foreach ($rows as $row) {
            Yii::$app->db->createCommand()->insert('{{%page_files}}', [
                'page_id' => $clone->id,
                'file_id' => $row['file_id'],
            ])->execute();
        }

        return $clone;
    }

    /**
     * Clones a page keeping the same model_group_id (Language variant).
     */
    public static function cloneLanguage(Page $source, array $overrides = []): Page
    {
        return Yii::$app->db->transaction(function () use ($source, $overrides) {
            $clone = new Page();
            $clone->attributes = $source->attributes;
            $clone->id = null;
            $clone->isNewRecord = true;

            // Keep existing group
            $clone->model_group_id = $source->model_group_id;

            return self::saveCloneInternal($clone, $source, $overrides);
        });
    }

    /**
     * Clones a page creating a new group (Total copy).
     */
    public static function cloneTotal(Page $source, array $overrides = []): Page
    {
        return Yii::$app->db->transaction(function () use ($source, $overrides) {
            $clone = new Page();
            $clone->attributes = $source->attributes;
            $clone->id = null;
            $clone->isNewRecord = true;

            // Reset group (Page::afterSave will assign new ID)
            $clone->model_group_id = null;

            return self::saveCloneInternal($clone, $source, $overrides);
        });
    }
}
