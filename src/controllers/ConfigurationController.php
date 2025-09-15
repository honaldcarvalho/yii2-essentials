<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\MetaTag;
use croacworks\essentials\models\Parameter;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\controllers\rest\StorageController;
use croacworks\essentials\services\Notify;
use yii\web\NotFoundHttpException;

/**
 * ConfigurationController implements the CRUD actions for Configuration model.
 */
class ConfigurationController extends AuthorizationController
{
    /**
     * {@inheritdoc}
     */
    public function __construct($id, $module, $config = array())
    {
        parent::__construct($id, $module, $config);;
    }
    /**
     * Lists all Configuration models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Configuration();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Configuration model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionPreview($id)
    {
        // Renderiza SOMENTE o HTML do e-mail (sem layout do site)
        $this->layout = false;

        /** @var \croacworks\essentials\models\EmailService $model */
        $cfg   = \croacworks\essentials\models\Configuration::findOne($id);
        $model = $cfg->emailService;

        // Suporta /preview?id=1&subject=...&content=...
        $subject = Yii::$app->request->get('subject');
        $content = Yii::$app->request->get('content');

        if ($subject === null || $subject === '') {
            $subject = 'Pré‑visualização — ' . ($cfg->title ?? Yii::$app->name);
        }

        if ($content === null || $content === '') {
            // HTML simples de exemplo (entra no {{content}})
            $resetUrl = Yii::$app->urlManager->createAbsoluteUrl(['site/index']);
            $content = "
            <p>Este é um preview do template de e‑mail usando os dados reais de configuração.</p>
            <table class='btn' role='presentation' border='0' cellpadding='0' cellspacing='0'>
                <tr><td align='center'>
                    <a href='{$resetUrl}' target='_blank' rel='noopener'>Ação de Exemplo</a>
                </td></tr>
            </table>
            <p style='opacity:.8'><small>Host: <strong>{$cfg->host}</strong> · Empresa: <strong>{$cfg->bussiness_name}</strong> · E‑mail: <a href='mailto:{$cfg->email}'>{$cfg->email}</a></small></p>
            ";
        }

        // Monta HTML final com placeholders reais (logo, título, etc.)
        $html = $model->renderTemplate([
            'subject' => $subject,
            'content' => $content,
        ]);

        // Força Content-Type HTML e evita download
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $html;
    }

    /**
     * Creates a new Configuration model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Configuration();

        if ($model->load(Yii::$app->request->post())) {

            // $file = \yii\web\UploadedFile::getInstance($model, 'file_id');

            // if (!empty($file) && $file !== null) {

            //     $arquivo = StorageController::uploadFile($file, ['save' => true]);

            //     if ($arquivo['success'] === true) {
            //         $model->file_id = $arquivo['data']['id'];
            //     }
            // }

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Configuration model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */

    public function actionUpdate($id)
    {

        $model = $this->findModel($id);
        $old = $model->file_id;
        $changed = false;
        $post = Yii::$app->request->post();

        if ($model->validate() && $model->load($post)) {

            if ($model->save()) {

                Yii::$app->notify->createForGroup($model->group_id, Yii::t('app','Configurations'), Yii::t('app','Configurations Updated'), 'system', "/configuration/{$model->id}", true, null);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        return $this->render('update', [
            'model' => $model,
        ]);
    }
    /**
     * Deletes an existing Configuration model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if ($id != 1) {
            $this->findModel($id)->delete();
        } else {
            \Yii::$app->session->setFlash('error', 'Is not possible exclude initial Configuration');
        }

        return $this->redirect(['index']);
    }


    public function actionClone($id)
    {
        $original = Configuration::findOne($id);
        if (!$original) {
            throw new NotFoundHttpException('A configuração não foi encontrada.');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Clona configuração principal
            $clone = new Configuration();
            $clone->attributes = $original->attributes;

            // Ajusta campos únicos
            if ($clone->hasAttribute('slug')) {
                $clone->slug .= '-clone-' . time();
            }
            if ($clone->hasAttribute('name')) {
                $clone->name .= ' (Clone)';
            }

            unset($clone->id);

            if (!$clone->save()) {
                throw new \Exception('Erro ao salvar configuração clonada.');
            }

            // Clona meta tags
            foreach ($original->metaTags as $meta) {
                $newMeta = new MetaTag();
                $newMeta->attributes = $meta->attributes;
                unset($newMeta->id);
                $newMeta->configuration_id = $clone->id;
                if (!$newMeta->save(false)) {
                    throw new \Exception('Erro ao clonar meta tag.');
                }
            }

            // Clona parâmetros
            foreach ($original->parameters as $param) {
                $newParam = new Parameter();
                $newParam->attributes = $param->attributes;
                unset($newParam->id);
                $newParam->configuration_id = $clone->id;
                if (!$newParam->save(false)) {
                    throw new \Exception('Erro ao clonar parâmetro.');
                }
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Configuração clonada com sucesso.');
            return $this->redirect(['view', 'id' => $clone->id]);
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("Erro ao clonar configuração: " . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Erro ao clonar a configuração: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }
}
