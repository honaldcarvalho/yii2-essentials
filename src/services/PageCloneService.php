<?php
// common/services/PageCloneService.php

namespace croacworks\essentials\services;

use Yii;

use croacworks\essentials\models\Page;
use croacworks\essentials\models\File;

/**
 * Clone service for Page.
 * Mirrors PostCloneService rules without changing business logic:
 * - Cover (file_id) is duplicated at DB-level (physical file reused)
 * - PageFiles (N:N) links are copied (reusing existing File IDs)
 * - cloneLanguage() keeps the same model_group_id as the source
 * - cloneTotal() starts a new group (model_group_id will be assigned on save as your app already does)
 * - Slug is forced to regenerate by setting it to null before save (Page::beforeValidate handles uniqueness)
 * - After save, we force-persist file_id because AttachFileBehavior may null it during save(false)
 * - deletePage() follows the same group-aware deletion policy as deletePost()
 */
class PageCloneService
{
    /**
     * Duplicate the File record used as cover (file_id).
     * It clones only the DB record; the physical file stays shared.
     *
     * @return int|null New File ID or null if source not found / could not be saved
     */
    private static function cloneCoverFile(int $fileId): ?int
    {
        /** @var File|null $sourceFile */
        $sourceFile = File::findOne($fileId);
        if (!$sourceFile) {
            return null;
        }

        $cloneFile = new File();
        $cloneFile->attributes = $sourceFile->attributes;
        $cloneFile->id = null;
        $cloneFile->isNewRecord = true;

        if (!$cloneFile->save(false)) {
            return null;
        }

        return (int)$cloneFile->id;
    }

    /**
     * Clone for language (keeps same model_group_id).
     */
    public static function cloneLanguage(Page $source, array $overrides = []): Page
    {
        return Yii::$app->db->transaction(function () use ($source, $overrides) {
            $clone = new Page();
            $clone->attributes = $source->attributes;
            $clone->id = null;

            $newFileId = null;

            // 1) Duplicate and assign cover (file_id)
            if ($source->file_id) {
                $newFileId = self::cloneCoverFile((int)$source->file_id);
                $clone->file_id = $newFileId;
            } else {
                $clone->file_id = null;
            }

            // keep same group
            $clone->model_group_id = $source->model_group_id;
            // force slug regeneration (Page::beforeValidate will generate one)
            $clone->slug = null;

            // Apply overrides
            foreach ($overrides as $k => $v) {
                $clone->$k = $v;
            }

            $clone->slug = $clone->generateUniqueSlug(null);
            // Save (beforeValidate may null file_id due to AttachFileBehavior)
            if (!$clone->save(false)) {
                throw new \RuntimeException('Failed to save language clone.');
            }

            // Force-persist file_id after save
            if ($newFileId !== null) {
                Page::updateAll(['file_id' => $newFileId], ['id' => $clone->id]);
                $clone->file_id = $newFileId; // keep in memory
            }

            // 2) Copy PageFiles (N:N) rows (reuse file_id)
            $rows = (new \yii\db\Query())
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
        });
    }

    /**
     * Total clone (new group).
     */
    public static function cloneTotal(Page $source, array $overrides = []): Page
    {
        return Yii::$app->db->transaction(function () use ($source, $overrides) {
            $clone = new Page();
            $clone->attributes = $source->attributes;
            $clone->id = null;

            $newFileId = null;

            // 1) Duplicate and assign cover (file_id)
            if ($source->file_id) {
                $newFileId = self::cloneCoverFile((int)$source->file_id);
                $clone->file_id = $newFileId;
            } else {
                $clone->file_id = null;
            }

            // new group -> let your existing logic assign a new model_group_id on save if that's how Post works
            $clone->model_group_id = null;
            $clone->slug = null;

            foreach ($overrides as $k => $v) {
                $clone->$k = $v;
            }
            
            $clone->slug = $clone->generateUniqueSlug(null);
            if (!$clone->save(false)) {
                throw new \RuntimeException('Failed to save total clone.');
            }

            // Force-persist file_id after save
            if ($newFileId !== null) {
                Page::updateAll(['file_id' => $newFileId], ['id' => $clone->id]);
                $clone->file_id = $newFileId;
            }

            // Ensure model_group_id exists after save
            if (empty($clone->model_group_id)) {
                $clone->refresh();
            }

            // Copy PageFiles links
            $rows = (new \yii\db\Query())
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
        });
    }

    /**
     * Delete respecting group rules (mirrors deletePost for Pages).
     */
    public static function deletePage(Page $page): void
    {
        Yii::$app->db->transaction(function () use ($page) {

            $groupId = (int)$page->model_group_id;

            // Count pages in group
            $countInGroup = (int)Page::find()->where(['model_group_id' => $groupId])->count();

            if ($countInGroup > 1) {
                // Case A: there are other pages in the group -> delete only this page and its pivot records

                // Keep current cover id to check orphaning later
                $coverFileIdToDelete = $page->file_id;

                // Remove page_files links for THIS page
                Yii::$app->db->createCommand()
                    ->delete('{{%page_files}}', ['page_id' => $page->id])
                    ->execute();

                // Delete this page
                Yii::$app->db->createCommand()
                    ->delete('{{%pages}}', ['id' => $page->id])
                    ->execute();

                // Cleanup: If cover file is now orphaned, remove it
                if ($coverFileIdToDelete) {
                    $stillReferenced = (new \yii\db\Query())
                        ->from('{{%pages}}')
                        ->where(['file_id' => $coverFileIdToDelete])
                        ->count();

                    if ($stillReferenced == 0) {
                        // Check in N:N too
                        $stillReferencedNN = (new \yii\db\Query())
                            ->from('{{%page_files}}')
                            ->where(['file_id' => $coverFileIdToDelete])
                            ->count();

                        if ($stillReferencedNN == 0) {
                            $f = File::findOne((int)$coverFileIdToDelete);
                            if ($f) {
                                $f->delete();
                            }
                        }
                    }
                }
            } else {
                // Case B: this is the last page in the group -> delete the whole group and cleanup orphans

                // Get all page IDs in this group
                $groupPageIds = (new \yii\db\Query())
                    ->select('id')->from('{{%pages}}')
                    ->where(['model_group_id' => $groupId])
                    ->column();

                if (empty($groupPageIds)) {
                    return;
                }

                // Collect file_ids from N:N (page_files)
                $n_n_fileIds = (new \yii\db\Query())
                    ->select('file_id')->from('{{%page_files}}')
                    ->where(['page_id' => $groupPageIds])
                    ->column();

                // Collect file_ids from 1:1 cover column (pages.file_id)
                $one_one_fileIds = (new \yii\db\Query())
                    ->select('file_id')->from('{{%pages}}')
                    ->where(['id' => $groupPageIds])
                    ->andWhere(['is not', 'file_id', null])
                    ->column();

                $fileIds = array_unique(array_filter(array_merge($n_n_fileIds, $one_one_fileIds)));

                // Delete pivot records and pages
                Yii::$app->db->createCommand()
                    ->delete('{{%page_files}}', ['page_id' => $groupPageIds])->execute();
                Yii::$app->db->createCommand()
                    ->delete('{{%pages}}', ['id' => $groupPageIds])->execute();

                // Cleanup any orphan files (not referenced by any page or page_files)
                if (!empty($fileIds)) {

                    $referenced_n_n = (new \yii\db\Query())
                        ->select(['file_id'])
                        ->from('{{%page_files}}')
                        ->where(['file_id' => $fileIds])
                        ->column();

                    $referenced_one_one = (new \yii\db\Query())
                        ->select(['file_id'])
                        ->from('{{%pages}}')
                        ->where(['file_id' => $fileIds])
                        ->andWhere(['is not', 'file_id', null])
                        ->column();

                    $stillReferenced = array_unique(array_filter(array_merge($referenced_n_n, $referenced_one_one)));
                    $toDelete = array_diff($fileIds, $stillReferenced);

                    if (!empty($toDelete)) {
                        foreach ($toDelete as $fid) {
                            $f = File::findOne((int)$fid);
                            if ($f) {
                                $f->delete();
                            }
                        }
                    }
                }
            }
        });
    }
}
