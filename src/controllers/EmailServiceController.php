<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\EmailService;
use croacworks\essentials\models\Configuration;
use yii\web\NotFoundHttpException;
use yii\symfonymailer\Mailer;

/**
 * EmailServiceController implements the CRUD actions for EmailService model.
 */
class EmailServiceController extends AuthorizationController
{
    /**
     * Lists all EmailService models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EmailService();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single EmailService model.
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
        $this->layout = false; // sem layout da aplicação
        $model = $this->findModel($id);

        // Renderiza direto o template substituindo placeholders fakes
        $html = $model->renderTemplate([
            'subject' => 'Exemplo de Assunto',
            'content' => '<p>Este é um preview do template de email.</p>',
        ]);

        return $html;
    }

    public function actionTest($id)
    {

        $model = $this->findModel($id);
        $params = Configuration::get();
        $mailer = new Mailer;

        $mailer->transport = [
            'scheme' => $model->scheme,
            'host' => $model->host,
            'encryption' => $model->enable_encryption ? $model->encryption : '',
            'username' => $model->username,
            'password' => $model->password,
            'port' => $model->port,
            'enableMailerLogging' => true
            //'dsn' => "{$model->scheme}://{$model->username}:{$model->password}@{$model->host}:{$model->port}"
        ];
        //$mailer->transport['dsn'] = "{$model->scheme}://{$model->username}:{$model->password}@{$model->host}:{$model->port}";

        $scheme = $_SERVER['REQUEST_SCHEME'];
        $url = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $content = " 
            <tr>
                <td>
                <p>Hi,</p>
                <p>This is a test email for the Weebz '{$params->title}'</b></p>
                    
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='btn btn-primary'>
                    <tbody>
                    <tr>
                        <td align='center'>
                        <table border='0' cellpadding='0' cellspacing='0' role='presentation' style='box-sizing:border-box'>
                            <tbody>
                                <tr>
                                    <td style='box-sizing:border-box'>
                                        <a href='{$scheme}://{$url}{$uri}' target='_blank' rel='noopener noreferrer' data-auth='NotApplicable' class='x_button x_button-primary' style='box-sizing:border-box; border-radius:4px; color:#fff; display:inline-block; overflow:hidden; text-decoration:none; background-color:#2d3748; border-bottom:8px solid #2d3748; border-left:18px solid #2d3748; border-right:18px solid #2d3748; border-top:8px solid #2d3748' data-safelink='true' data-linkindex='1'>Visualizar</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p><small>" . Yii::t('app', 'This message was sent automatically by the system') . " {$params->title}, don't answer.</small></p>
                </td>
            </tr>
        ";
        $subject = 'Test Email - ' . $model->description;
        $message = $mailer->compose('@vendor/croacworks/yii2-essentials/src/mail/layouts/template', ['subject' => $subject, 'content' => $content]);
        $response = $message->setFrom($model->username)->setTo($params->email)
            ->setSubject(Yii::t('app', 'Test Email - ' . $model->description))
            ->send();

        if ($response) {
            \Yii::$app->session->setFlash('success', "Email sended to {$params->email}.  See you email.");
        } else {
            foreach (Yii::getLogger()->messages as $key => $message) {
                if ($message[2] == 'yii\symfonymailer\Mailer::sendMessage') {
                    \Yii::$app->session->setFlash('error', 'Occoured some error: ' . $message[0]);
                }
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);

        //$this->sendEmail('Vistoria - Piauí Conectado','central.servicos@piauiconectado.com.br', 'honaldcarvalho@gmail.com', 'Vistoria - Pendências', $content);
        //$this->sendEmail('System Basic','suporte@weebz.com.br', "Teste de email: {$model->description}", $content);
    }

    /**
     * Creates a new EmailService model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new EmailService();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing EmailService model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $old_password = $model->password;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing EmailService model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the EmailService model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return EmailService the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $status = null)
    {
        if (($model = EmailService::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
