<?php

namespace croacworks\essentials\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use croacworks\essentials\controllers\rest\StorageController;

/**
 * AttachFileBehavior
 *
 * Automatically handles file upload for a model attribute (e.g., file_id).
 * 
 * Usage:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => \croacworks\essentials\behaviors\AttachFileBehavior::class,
 *             'attribute' => 'file_id', // the field in DB
 *         ],
 *     ];
 * }
 * ```
 */
class AttachFileBehavior extends Behavior
{
    /**
     * @var string the model attribute that stores file_id
     */
    public string $attribute = 'file_id';

    /**
     * @var UploadedFile|null
     */
    protected ?UploadedFile $uploadedFile = null;

    /**
     * Events
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'handleUpload',
        ];
    }

    /**
     * Handles file upload before validation
     */
    public function handleUpload()
    {
        $owner = $this->owner;
        $attr = $this->attribute;

        $this->uploadedFile = UploadedFile::getInstance($owner, $attr);

        if ($this->uploadedFile instanceof UploadedFile) {
            $result = StorageController::uploadFile($this->uploadedFile, ['save' => true]);

            if ($result['success'] === true && isset($result['data']['id'])) {
                $file_id = $this->owner->{$attr};
                if($file_id)
                    StorageController::removeFile($file_id);
                $owner->$attr = $result['data']['id'];
            } else {
                // In case upload fails, add model error
                $owner->addError($attr, Yii::t('app', 'Failed to upload file.'));
            }
        }
    }
}
