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
    public function behaviors()
    {
        return [
            [
                'class' => \croacworks\essentials\behaviors\AttachFileBehavior::class,
                'attribute'           => 'file_id',
                'deleteOnOwnerDelete' => true,
                'deleteOldOnReplace'  => true,
                'thumbAspect'         => 1,
                'folderId'            => 1,
                'groupId'             => 1,
                'saveModel'           => true,
                'removeFlagParam'     => 'remove',
                'removeFlagScoped'    => true, // se o hidden vier como Model[remove]
            ],
        ];
    }
 */

class AttachFileBehavior extends Behavior
{
    /** Campo no AR que guarda o ID do arquivo (ex.: "file_id") */
    public string $attribute = 'file_id';

    /** Apaga o arquivo vinculado quando o dono é deletado */
    public bool $deleteOnOwnerDelete = true;

    /** Apaga o arquivo antigo quando um novo upload substitui */
    public bool $deleteOldOnReplace = true;

    /** 1 (quadrada) ou "LARGURA/ALTURA" para thumbs */
    public int|string $thumbAspect = 1;

    /** 1=auto; 2=img; 3=video; 4=doc */
    public int $folderId = 1;

    public ?int $groupId = 1;

    /** Se deve persistir na tabela `file` */
    public bool $saveModel = true;

    /** 🔽 NOVO: nome do hidden que marca remoção no submit (ex.: "remove") */
    public string $removeFlagParam = 'remove';

    /** 🔽 NOVO: se true, flag vem escopada como Model[remove] */
    public bool $removeFlagScoped = false;

    /** Guarda o ID antigo para limpeza em substituição */
    private ?int $oldId = null;

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND      => 'captureOldId',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'handleUploadBeforeValidate',
            ActiveRecord::EVENT_AFTER_DELETE    => 'onOwnerAfterDelete',
        ];
    }

    public function captureOldId(): void
    {
        $attr = $this->attribute;
        $this->oldId = (int)($this->owner->{$attr} ?? 0) ?: null;
    }

    /**
     * - Se veio hidden de remoção e NÃO há novo upload: apaga e zera o atributo.
     * - Se há novo upload: faz upload, seta novo ID e (opcional) apaga o antigo.
     */
    public function handleUploadBeforeValidate(): void
    {
        $owner = $this->owner;
        $attr  = $this->attribute;

        $uploaded = UploadedFile::getInstance($owner, $attr);
        $removeAsked = $this->isRemoveRequested();

        /** @var StorageService $storage */
        $storage = Yii::$app->storage;

        // 1) Somente remoção (sem novo arquivo)
        if (!$uploaded && $removeAsked) {
            $id = (int)($owner->{$attr} ?? 0);
            if ($id > 0) {
                try { $storage->deleteById($id); }
                catch (\Throwable $e) { Yii::warning(['attach_behavior_remove' => $e->getMessage()], __METHOD__); }
            }
            $owner->{$attr} = null;      // ou 0, conforme seu schema
            $this->oldId    = null;
            return;
        }

        // 2) Novo upload (substituição ou inclusão)
        if (!$uploaded instanceof UploadedFile) {
            return; // nada a fazer
        }

        try {
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

            if ($res instanceof \yii\db\BaseActiveRecord) {
                if ($res->hasErrors()) {
                    $owner->addError($attr, 'Falha ao salvar arquivo: ' . json_encode($res->getErrors(), JSON_UNESCAPED_UNICODE));
                    return;
                }
                $newId = (int)$res->id;
                $owner->{$attr} = $newId;
            } else {
                // sem persistir não temos ID -> não altera atributo
                return;
            }

            // limpar antigo se habilitado e houve troca
            if ($this->deleteOldOnReplace && $this->oldId && $this->oldId !== (int)$owner->{$attr}) {
                try { $storage->deleteById($this->oldId); }
                catch (\Throwable $e) { Yii::warning(['attach_behavior_replace_cleanup' => $e->getMessage()], __METHOD__); }
            }

            $this->oldId = (int)$owner->{$attr};

        } catch (\Throwable $e) {
            Yii::error("AttachFileBehavior upload exception: {$e->getMessage()}", __METHOD__);
            $owner->addError($attr, 'Não foi possível processar o upload do arquivo.');
        }
    }

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

    /**
     * Lê a flag de remoção do POST:
     * - se `$removeFlagScoped = true`, lê `$_POST[ModelFormName][removeFlagParam]`
     * - senão, lê `$_POST[removeFlagParam]`
     */
    private function isRemoveRequested(): bool
    {
        $request = Yii::$app->getRequest();
        if (!method_exists($request, 'post')) {
            return false; // console/sem web request
        }
        $params = $request->post();
        if ($this->removeFlagScoped) {
            $form = $this->owner->formName();
            $val  = $params[$form][$this->removeFlagParam] ?? null;
        } else {
            $val  = $params[$this->removeFlagParam] ?? null;
        }
        if ($val === null) return false;
        // aceita 1, "1", true, "true", "on"
        if (is_bool($val)) return $val;
        $s = is_string($val) ? strtolower($val) : (string)$val;
        return $s === '1' || $s === 'true' || $s === 'on';
    }
}