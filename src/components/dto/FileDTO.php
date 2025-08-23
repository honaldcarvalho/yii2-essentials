<?php
namespace croacworks\essentials\components\dto;

class FileDTO
{
    public int $groupId;
    public int $folderId;
    public string $name;
    public string $description;
    public string $path;       // caminho relativo (ex: /uploads/images/abc.jpg)
    public string $url;        // url pública
    public ?string $pathThumb = null;
    public ?string $urlThumb  = null;
    public string $extension;
    public string $type;       // image|video|doc
    public int $size = 0;      // bytes
    public int $duration = 0;  // segundos (vídeo)
    public string $createdAt;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
