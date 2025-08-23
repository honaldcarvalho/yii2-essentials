# Guia de Uso — Storage & Mídia (`croacworks\essentials`)

Este guia explica **como usar** o novo stack de Storage em camadas:

- **Service**: `croacworks\essentials\components\StorageService`
- **Jobs** (fila opcional): `TranscodeVideoJob`, `GenerateThumbJob`, `VideoProbeDurationJob`
- **Controller fino**: `croacworks\essentials\controllers\StorageController`
- **Behavior**: `croacworks\essentials\behaviors\AttachFileBehavior`
- **Widgets**: `FileUploadInput`, `MediaPicker`, `UploadImageInstant`

> Ponto-chave: se a fila (`yii2-queue`) não estiver ativa, **tudo funciona em modo síncrono** (fallback). Se a fila estiver ativa, **thumbs/transcode/probe** rodam como **jobs**.

---

## 1) Requisitos

- PHP 8.x
- Extensões: `fileinfo`, `gd` ou `imagick` (para `yii2-imagine`)
- Dependências PHP:
  - `yiisoft/yii2-imagine`
  - `php-ffmpeg/php-ffmpeg`
  - (opcional) `yiisoft/yii2-queue` + driver (DB/Redis/AMQP)
- Binários (no servidor): `ffmpeg` e `ffprobe` no `PATH`.

---

## 2) Instalação & Configuração

### 2.1 Componentes (`config/main.php`)

```php
'components' => [
  'storage' => [
    'class' => \croacworks\essentials\components\StorageService::class,
    'driver' => [
      'class'    => \croacworks\essentials\components\storage\LocalStorageDriver::class,
      'basePath' => '@webroot/uploads',
      'baseUrl'  => '@web/uploads',
    ],
    'defaultThumbSize' => 300,
    'enableQueue'      => true, // se houver queue, jobs são enfileirados
  ],

  // opcional: fila (exemplo com DB)
  'queue' => [
    'class' => \yii\queue\db\Queue::class,
    'db' => 'db',
    'tableName' => '{{%queue}}',
    'channel' => 'storage',
    'ttr' => 600,
    'attempts' => 2,
  ],
],
```

### 2.2 Rotas

```php
'components' => [
  'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
      'GET storage/list'               => 'storage/list',
      'GET storage/info'               => 'storage/info',
      'GET storage/download'           => 'storage/download',
      'POST storage/upload'            => 'storage/upload',
      'POST storage/update'            => 'storage/update',
      'POST storage/delete'            => 'storage/delete',
      'DELETE storage/delete'          => 'storage/delete',
      'POST storage/move'              => 'storage/move',
      'POST storage/replace'           => 'storage/replace',
      'POST storage/attach'            => 'storage/attach',
      'POST storage/detach'            => 'storage/detach',
      'DELETE storage/detach'          => 'storage/detach',
      'POST storage/regenerate-thumb'  => 'storage/regenerate-thumb',
      'POST storage/transcode'         => 'storage/transcode',
      'POST storage/probe-duration'    => 'storage/probe-duration',
    ],
  ],
],
```

> **Importante**: desative antigas rotas do `controllers\rest\StorageController`. O novo controller é `croacworks\essentials\controllers\StorageController`.

### 2.3 Alias (se necessário)

Se sua extensão ainda não define o alias base, no bootstrap:

```php
Yii::setAlias('@croacworks/essentials', dirname(__DIR__));
```

---

## 3) Modelo & Migração (tabela `file`)

Campos esperados no ActiveRecord `File`:

```
id, group_id, folder_id, name, description, path, url,
pathThumb, urlThumb, extension, type, size, duration,
created_at, updated_at
```

_Sugestão de migração (resumida):_

```php
$this->createTable('{{%file}}', [
  'id' => $this->primaryKey(),
  'group_id' => $this->integer()->notNull()->defaultValue(1),
  'folder_id'=> $this->integer()->notNull()->defaultValue(1),
  'name' => $this->string()->notNull(),
  'description' => $this->string(),
  'path' => $this->string()->notNull(), // ex: /uploads/images/foo.jpg
  'url'  => $this->string()->notNull(), // ex: /uploads/images/foo.jpg (prefixo @web)
  'pathThumb' => $this->string()->null(),
  'urlThumb'  => $this->string()->null(),
  'extension' => $this->string(16)->notNull(),
  'type' => $this->string(16)->notNull(), // image|video|doc
  'size' => $this->bigInteger()->notNull()->defaultValue(0),
  'duration' => $this->integer()->notNull()->defaultValue(0),
  'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
  'updated_at' => $this->integer()->null(),
]);
```

---

## 4) `StorageService` — API

### 4.1 Upload

```php
/** @var \croacworks\essentials\components\StorageService $storage */
$storage = Yii::$app->storage;
$res = $storage->upload($uploadedFile, new \croacworks\essentials\components\dto\StorageOptions([
  'fileName'     => null,
  'description'  => $uploadedFile->name,
  'folderId'     => 1,
  'groupId'      => 1,
  'saveModel'    => true,   // true => retorna ActiveRecord File
  'convertVideo' => true,   // converte para mp4 se necessário
  'thumbAspect'  => 1,      // 1 ou "LARGURA/ALTURA"
  'quality'      => 85,
]));
```

- **Imagem**: cria thumb (quadrada por padrão ou no aspect escolhido).
- **Vídeo**: (opcional) transcodifica para MP4, cria thumb (frame aos 2s) e mede duração.
- **Docs**: salva em `/uploads/docs`.
- Se `saveModel=true`, retorna o **AR `File`**; caso contrário, retorna um **DTO** com dados do arquivo sem persistir.

> Em erro de persistência, o service **apaga** arquivo e thumb criados (requisito do projeto).

### 4.2 Deleção por ID

```php
$ok = Yii::$app->storage->deleteById($fileId);
```

Remove do DB e do disco (arquivo e thumb, se existirem).

---

## 5) Controller de Storage — Endpoints

### 5.1 Upload
`POST /storage/upload` (multipart/form-data)

Campos:
- `file` (obrigatório)
- `file_name`, `description`, `folder_id`, `group_id`, `save`(1/0), `convert_video`(1/0), `thumb_aspect`(1 ou `W/H`), `quality`

Resposta:
```json
{ "ok": true, "data": { "id": 123, "url": "/uploads/...", ... } }
```

### 5.2 Info
`GET /storage/info?id=123`

### 5.3 List
`GET /storage/list?folder_id=&type=&q=&page=&pageSize=`

### 5.4 Download
`GET /storage/download?id=123`

### 5.5 Update (renomear/descrição)
`POST /storage/update?id=123` com `name`, `description`

### 5.6 Delete
`POST /storage/delete?id=123` (ou `DELETE`)

### 5.7 Move de pasta
`POST /storage/move?id=123` com `folder_id`

### 5.8 Replace conteúdo
`POST /storage/replace?id=123` com `file` (substitui mantendo o mesmo registro)

### 5.9 Attach / Detach (pivot)
`POST /storage/attach` com `class_name`, `file_id`, `model_id`, `field_model_id`, `field_file_id`

`POST|DELETE /storage/detach` com os mesmos campos

### 5.10 Regenerar thumb
`POST /storage/regenerate-thumb?id=123&aspect=1|W/H`

### 5.11 Transcode para MP4
`POST /storage/transcode?id=123`

### 5.12 Medir duração
`POST /storage/probe-duration?id=123`

#### Exemplos rápidos (cURL)

```bash
# Upload de imagem
curl -F "file=@/caminho/img.jpg" -F save=1 -F thumb_aspect=1 http://host/storage/upload

# Info
curl 'http://host/storage/info?id=123'

# Delete
curl -X POST 'http://host/storage/delete?id=123'
```

---

## 6) Jobs (fila opcional)

- `GenerateThumbJob`: imagem/vídeo → cria thumb (e redimensiona até 300px por padrão).
- `TranscodeVideoJob`: converte para MP4 **in-place**.
- `VideoProbeDurationJob`: mede duração via `ffprobe` e salva no `File`.

> Com `enableQueue=true` e `queue` configurada, o serviço **enfileira**; caso contrário, executa síncrono.

---

## 7) `AttachFileBehavior`

Use no seu **ActiveRecord** para integrar formulário + upload + limpeza automática.

```php
public function behaviors()
{
  return [
    [
      'class' => \croacworks\essentials\behaviors\AttachFileBehavior::class,
      'attribute'           => 'file_id',  // campo no seu AR
      'deleteOnOwnerDelete' => true,       // apaga arquivo quando o dono é deletado
      'deleteOldOnReplace'  => true,       // apaga arquivo antigo ao substituir
      'thumbAspect'         => 1,          // 1 ou "W/H"
      'folderId'            => 1,          // 1=auto por tipo; 2=img; 3=video; 4=doc
      'groupId'             => 1,
      'saveModel'           => true,
    ],
  ];
}
```

Fluxo:
- No `EVENT_BEFORE_VALIDATE`, se existir arquivo no campo (ex.: `file_id`), o behavior chama `StorageService->upload(...)`, substitui o valor do atributo com o **novo ID** e, se configurado, apaga o antigo.
- No `EVENT_AFTER_DELETE` do dono, apaga o arquivo vinculado (se configurado).

> Para remoção via UI, envie um hidden `remove=1` (nome configurável) — o behavior pode ler isso para desvincular e apagar.

---

## 8) Widgets

### 8.1 `FileUploadInput`

Input completo (hidden + file) com **upload automático** e **preview**.

```php
use croacworks\essentials\widgets\FileUploadInput;

echo FileUploadInput::widget([
  'model' => $model,
  'attribute' => 'file_id',
  'accept' => 'image/*',
  'deleteOnClear' => false, // true => chama /storage/delete ao limpar
  'thumbAspect' => 1,       // ou "300/200"
]);
```

### 8.2 `MediaPicker`

Botão que abre a biblioteca de mídia (grid) e preenche um input alvo com o **ID** escolhido.

```php
use croacworks\essentials\widgets\MediaPicker;

echo MediaPicker::widget([
  'targetInputId' => Html::getInputId($model, 'file_id'),
  'label' => 'Selecionar da mídia',
]);
```

### 8.3 `UploadImageInstant`

Widget com **CropperJS** e compressão client-side.

**Modo defer (recomendado em forms):**
```php
use croacworks\essentials\widgets\UploadImageInstant;

// campo file oculto do AR
echo $form->field($model, 'file_id')->fileInput([ 'accept' => 'image/*', 'style' => 'display:none' ])->label(false);

// widget
echo UploadImageInstant::widget([
  'mode'        => 'defer',      // injeta o arquivo no input file do form
  'model'       => $model,
  'attribute'   => 'file_id',
  'imageUrl'    => $model->file->url ?? '',
  'aspectRatio' => '16/9',       // '1', '16/9' ou 'NaN'
]);
```

**Modo instant (envia já para /storage/upload e preenche um hidden):**
```php
echo UploadImageInstant::widget([
  'mode'               => 'instant',
  'model'              => $model,   // precisa ter PK
  'attribute'          => 'file_id',
  'imageUrl'           => $model->file->url ?? '',
  'aspectRatio'        => '1',
  'deleteOldOnReplace' => true,     // apaga o antigo via /storage/delete
  // opcional: pivot
  //'attactModelClass' => \common\models\PostFile::class,
  //'attactModelFields'=> ['model_id','file_id'],
]);
```

Eventos JS úteis:
```js
document.addEventListener('uploadImage:pending', (e) => console.log('vai subir no submit', e.detail));
document.addEventListener('uploadImage:saved', (e) => console.log('salvo', e.detail.file));
```

---

## 9) Fluxos de uso

### 9.1 CREATE com imagem
- Form com `UploadImageInstant (defer)` ou `FileUploadInput`.
- Ao submeter, o `AttachFileBehavior` fará o upload, criará thumb e persistirá.

### 9.2 UPDATE trocando imagem
- `UploadImageInstant` em `defer` ou `instant`.
- Se `deleteOldOnReplace=true` (instant), o widget chama `/storage/delete` para remover o antigo após o novo upload.
- Em `defer`, o Behavior apaga o antigo após salvar.

### 9.3 Vídeos
- `convertVideo=true` para transcodificar MP4.
- Thumb gerada do frame 2s.
- Duração preenchida por job (ou síncrono sem fila).

---

## 10) Substituindo o antigo `controllers\rest\StorageController`

1. **Remova** importações e chamadas estáticas antigas (`uploadFile`, `removeFile`).
2. **Troque** por `Yii::$app->storage->upload(...)` e `Yii::$app->storage->deleteById($id)`.
3. **Habilite** as rotas novas do `StorageController` fino (listadas acima).
4. **Atualize** behaviors/forms/widgets para usar os endpoints `/storage/*` (este guia já atende isso).

---

## 11) Troubleshooting

- **Thumb não gera**: verifique permissões em `@webroot/uploads` e extensão `gd/imagick`.
- **Vídeo não transcodifica**: confirme `ffmpeg`/`ffprobe` no PATH e `php-ffmpeg` instalado.
- **Fila não executa**: rode o worker (`yii queue/listen`) e confira a tabela `queue`.
- **CSRF**: endpoints usam `same-origin`. Em SPAs, inclua o token no `FormData` (`_csrf`).
- **URL/Path incorretos**: confira `basePath/baseUrl` do `LocalStorageDriver`.

---

## 12) Extensões úteis (Roadmap)

- `MultiFileUploadInput` + galeria (ordenar/remover inline)
- Suporte S3/GCS/Azure (novo driver de storage)
- WebP/AVIF automático para imagens
- Regras de ACL por `group_id`/RBAC em endpoints

---

### Contato
Se quiser, eu gero um **patch (.diff)** aplicando todas as mudanças sugeridas e um exemplo de **módulo** `modules/storage` com rotas namespaced.

