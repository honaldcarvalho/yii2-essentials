<?php
namespace croacworks\essentials\components\storage;

use yii\web\UploadedFile;
use croacworks\essentials\components\dto\FileDTO;
use croacworks\essentials\components\dto\StorageOptions;

interface StorageDriverInterface
{
    public function save(UploadedFile $file, StorageOptions $opts): FileDTO;
    public function delete(string $absolutePath): bool;
    public function exists(string $absolutePath): bool;
    public function absolutePath(string $relativePath): string;
}
