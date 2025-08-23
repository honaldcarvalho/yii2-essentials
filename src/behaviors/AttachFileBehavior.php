<?php
namespace croacworks\essentials\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

use croacworks\essentials\components\StorageService;
use croacworks\essentials\components\dto\StorageOptions;

/**
 * AttachFileBehavior
 *
 * - Faz upload do arquivo vindo do form e atualiza o atributo do AR com o ID do File salvo.
 * - Opcionalmente apaga o arquivo antigo ao substituir.
 * - Opcionalmente apaga o arquivo quando o dono é excluído.
 *
 * Uso (no modelo dono):
 *   public function behaviors()
 *   {
 *       return [
 *           [
 *               'class' => \croacworks\essentials\behaviors\AttachFileBehavior::class,
 *               'attribute' => 'file_id',        // campo integer no seu AR
 *               'deleteOnOwnerDelete' => true,
 *               'deleteOldOnReplace'  => true,
 *               'thumbAspect' => 1,              // 1 ou "LARGURA/ALTURA"
 *               'folderId'   => 1,               // 1=auto por tipo; 2=img; 3=video; 4=doc
 *               'groupId'    => 1,
 *               'saveModel'  => true,            // salva registro na tabela `file`
 *           ],
 *       ];
 *   }
 */
class AttachFileBehavior extends Behavior
{
    /** @var string Nome do atributo no dono que guarda o ID do arquivo (ex.: "file_id") */
    public string $attribute = 'file_id';

    /** @var bool Apaga o arquivo vinculado quando o dono é deletado */
    public bool $deleteOnOwnerDelete = true;

    /** @var bool Apaga o arquivo antigo quando um novo upload substitui */
    public bool $deleteOldOnReplace = true;

    /** @var int|string 1 (quadrada) ou "LARGURA/ALTURA" para thumbs */
    public int|string $thumbAspect = 1;

    /** @var int Pasta lógica (1=auto; 2=img; 3=video; 4=doc) */
    public int $folderId = 1;

    /** @var int|null Grupo (se seu sistema usa multi-grupo) */
    public ?int $groupId = 1;

    /** @var bool Se deve persistir na tabela `file` */
    public bool $saveModel = true;

    /** @var int|null Guarda o ID antigo para eventual limpeza */
    private ?int $oldId = null;

    /**
     * Mapeia eventos do dono.
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND      => 'captureOldId',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'handleUploadBeforeValidate',
            ActiveRecord::EVENT_AFTER_DELETE    => 'onOwnerAfterDelete',
        ];
    }

    /**
     * Captura o valor atual (para poder deletar o antigo em caso de substituição).
     */
    public function captureOldId(): void
    {
        $attr = $this->attribute;
        $this->oldId = (int)($this->owner->{$attr} ?? 0) ?: null;
    }

    /**
     * Executa o upload antes da validação do dono.
     * Se houver arquivo no campo ($this->attribute), faz upload,
     * grava o ID no atributo e (opcional) apaga o antigo.
     */
    public function handleUploadBeforeValidate(): void
    {
        $owner = $this->owner;
        $attr  = $this->attribute;

        $uploaded = UploadedFile::getInstance($owner, $attr);
        if (!$uploaded instanceof UploadedFile) {
            return; // nada para fazer
        }

        try {
            /** @var StorageService $storage */
            $storage = Yii::$app->storage;

            $opts = new StorageOptions([
                'fileName'     => null,
                'description'  => $uploaded->name,
                'folderId'     => $this->folderId,
                'groupId'      => $this->groupId ?? 1,
                'saveModel'    => $this->saveModel,
                'convertVideo' => true,
                'thumbAspect'  => $this->thumbAspect,
                'quality'      => 85,
            ]);

            $res = $storage->upload($uploaded, $opts);

            // Se veio um ActiveRecord, checa erros
            if ($res instanceof \yii\db\BaseActiveRecord) {
                if ($res->hasErrors()) {
                    $owner->addError($attr, 'Falha ao salvar arquivo: ' . json_encode($res->getErrors(), JSON_UNESCAPED_UNICODE));
                    return;
                }
                // sucesso: seta o novo ID no atributo
                $newId = (int)$res->id;
                $owner->{$attr} = $newId;
            } else {
                // Caso saveModel = false, não temos ID; você pode adaptar para salvar via service e obter um ID
                // Por padrão, não altera o atributo.
                // Se quiser obrigar saveModel=true, valide isso no construtor/uso.
                return;
            }

            // Apagar o antigo se for uma substituição e estiver habilitado
            if ($this->deleteOldOnReplace && $this->oldId && $owner->{$attr} && $this->oldId !== (int)$owner->{$attr}) {
                try {
                    $storage->deleteById($this->oldId);
                } catch (\Throwable $e) {
                    Yii::warning(['attach_behavior_replace_cleanup' => $e->getMessage()], __METHOD__);
                }
            }

            // Atualiza o oldId capturado para futuras trocas nesta mesma request
            $this->oldId = (int)$owner->{$attr};

        } catch (\Throwable $e) {
            Yii::error("AttachFileBehavior upload exception: {$e->getMessage()}", __METHOD__);
            $owner->addError($attr, 'Não foi possível processar o upload do arquivo.');
        }
    }

    /**
     * Remove o arquivo vinculado quando o dono é deletado (se configurado).
     */
    public function onOwnerAfterDelete(): void
    {
        if (!$this->deleteOnOwnerDelete) {
            return;
        }
        $attr = $this->attribute;
        $id   = (int)($this->owner->{$attr} ?? 0);
        if ($id > 0) {
            try {
                /** @var StorageService $storage */
                $storage = Yii::$app->storage;
                $storage->deleteById($id);
            } catch (\Throwable $e) {
                Yii::warning(['attach_behavior_delete' => $e->getMessage()], __METHOD__);
            }
        }
    }
}
