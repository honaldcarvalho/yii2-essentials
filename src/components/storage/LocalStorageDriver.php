<?php
namespace croacworks\essentials\components\storage;

use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use croacworks\essentials\components\dto\FileDTO;
use croacworks\essentials\components\dto\StorageOptions;

class LocalStorageDriver implements StorageDriverInterface
{
    public string $basePath = '@webroot/uploads'; // config
    public string $baseUrl  = '@web/uploads';     // config

    public function save(UploadedFile $file, StorageOptions $opts): FileDTO
    {
        $basePath = Yii::getAlias($this->basePath);
        $baseUrl  = Yii::getAlias($this->baseUrl);

        // pastas por tipo (image/video/doc) serão decididas fora (no service)
        // aqui apenas garante diretório e move arquivo final
        $relativeDir = $this->decideRelativeDirByMime($file->type, $opts->folderId);
        $absDir = $basePath . $relativeDir;
        FileHelper::createDirectory($absDir);

        $ext  = strtolower($file->getExtension() ?: pathinfo($file->name, PATHINFO_EXTENSION));
        $stem = $opts->fileName ?: ('file_' . date('YmdHis') . Yii::$app->security->generateRandomString(6));
        $name = $stem . '.' . $ext;

        $relativePath = $relativeDir . '/' . $name;
        $absolutePath = $basePath . $relativePath;

        if (!$file->saveAs($absolutePath)) {
            throw new \RuntimeException('Falha ao salvar arquivo no disco.');
        }

        $dto = new FileDTO();
        $dto->groupId     = (int)($opts->groupId ?? 1);
        $dto->folderId    = $this->normalizeFolderId($file->type, $opts->folderId);
        $dto->name        = $name;
        $dto->description = $opts->description ?? $file->name;
        $dto->path        = '/uploads' . $relativePath;          // relativo webroot
        $dto->url         = $baseUrl . $relativePath;            // url pública
        $dto->extension   = $ext;
        $dto->type        = $this->detectType($file->type);
        $dto->size        = filesize($absolutePath) ?: 0;
        $dto->createdAt   = date('Y-m-d H:i:s');

        return $dto;
    }

    public function delete(string $absolutePath): bool
    {
        return is_file($absolutePath) ? @unlink($absolutePath) : false;
    }

    public function exists(string $absolutePath): bool
    {
        return is_file($absolutePath);
    }

    public function absolutePath(string $relativePath): string
    {
        return Yii::getAlias($this->basePath) . $relativePath;
    }

    private function detectType(?string $mime): string
    {
        if (!$mime || strpos($mime, '/') === false) return 'doc';
        [$t] = explode('/', $mime);
        return in_array($t, ['image','video'], true) ? $t : 'doc';
    }

    private function decideRelativeDirByMime(?string $mime, int $folderId): string
    {
        $type = $this->detectType($mime);
        if ($folderId === 1) {
            // auto por tipo
            return match ($type) {
                'image' => '/images',
                'video' => '/videos',
                default => '/docs',
            };
        }
        // caso queira forçar pelo folderId
        return match ($folderId) {
            2 => '/images',
            3 => '/videos',
            4 => '/docs',
            default => '/docs',
        };
    }

    private function normalizeFolderId(?string $mime, int $folderId): int
    {
        if ($folderId !== 1) return $folderId;
        return match ($this->detectType($mime)) {
            'image' => 2,
            'video' => 3,
            default => 4,
        };
    }
}
