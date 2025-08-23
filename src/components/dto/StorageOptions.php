<?php
namespace croacworks\essentials\components\dto;

class StorageOptions
{
    public ?string $fileName = null;     // nome final (sem extensão)
    public ?string $description = null;
    public int $folderId = 1;            // 1=auto; 2=img; 3=video; 4=doc (exemplo)
    public ?int $groupId = 1;            // grupo atual
    public bool $saveModel = true;       // se deve persistir em `File` (AR)
    public bool $convertVideo = true;    // converter para mp4
    public string|int $thumbAspect = 1;  // 1 (quadrada) ou "300/200"
    public int $quality = 85;            // qualidade padrão

    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) $this->$k = $v;
        }
    }
}
