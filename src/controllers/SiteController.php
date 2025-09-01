<?php

namespace croacworks\essentials\controllers;

use croacworks\essentials\models\forms\LoginForm;
use croacworks\essentials\models\forms\PasswordResetRequestForm;
use croacworks\essentials\models\forms\ResendVerificationEmailForm;
use croacworks\essentials\models\forms\ResetPasswordForm;
use croacworks\essentials\models\forms\VerifyEmailForm;
use Yii;
use InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use yii\helpers\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

/**
 * Site controller
 */
class SiteController extends CommonController
{

    public function __construct($id, $module, $config = array())
    {
        parent::__construct($id, $module, $config);
        $this->free = array_merge($this->free, ['reset-password', 'request-password-reset', 'verify-email', 'login', 'logout']);
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays Dashboard.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('@croacworks/essentials/views/site/index');
    }

    public function actionExport($format = 'csv', $filename = 'export')
    {
        Yii::$app->response->format = Response::FORMAT_RAW;

        // Obtem os dados do POST (data serializada)
        $raw = Yii::$app->request->post('export_data');
        $columns = Yii::$app->request->post('export_columns', []);

        if (!$raw || !$columns) {
            throw new \yii\web\BadRequestHttpException("Dados para exportação não enviados.");
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \yii\web\BadRequestHttpException("Formato de dados inválido.");
        }

        $headers = $columns;

        if ($format === 'csv') {
            header("Content-Type: text/csv");
            header("Content-Disposition: attachment; filename={$filename}.csv");
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($data as $row) {
                $line = [];
                foreach ($headers as $key) {
                    $line[] = $row[$key] ?? '';
                }
                fputcsv($output, $line);
            }
            fclose($output);
            return;
        }

        if ($format === 'excel') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray([$headers], NULL, 'A1');
            foreach ($data as $i => $row) {
                $line = [];
                foreach ($headers as $key) {
                    $line[] = $row[$key] ?? '';
                }
                $sheet->fromArray([$line], NULL, 'A' . ($i + 2));
            }
            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment;filename=\"{$filename}.xlsx\"");
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            return;
        }

        if ($format === 'pdf') {
            $html = '<h2>' . Html::encode($filename) . '</h2><table border="1" cellpadding="5"><thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . Html::encode($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($headers as $key) {
                    $html .= '<td>' . Html::encode($row[$key] ?? '') . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';

            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);
            return $mpdf->Output("{$filename}.pdf", \Mpdf\Output\Destination::DOWNLOAD);
        }

        throw new \yii\web\BadRequestHttpException("Formato de exportação inválido.");
    }
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionDashboard()
    {
        return $this->render('dashboard');
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionCkeditor()
    {
        return $this->render('ckeditor');
    }


    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'main-login';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        // ==== BEGIN Soft Mode: deactivate session ====
        try {
            $sessionId = Yii::$app->session->id;
            Yii::$app->db->createCommand('
            UPDATE {{%user_active_sessions}}
            SET is_active = 0
            WHERE session_id = :sid
        ', [':sid' => $sessionId])->execute();
        } catch (\Throwable $e) {
            // ignora se tabela ainda não existir
        }
        // ==== END deactivate session ====

        Yii::$app->user->logout();
        return $this->goHome();
    }



    public function actionRequestPasswordReset()
    {
        $this->layout = 'main-login';

        $model = new PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {

        $this->layout = 'main-login';

        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if (($user = $model->verifyEmail()) && Yii::$app->user->login($user)) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }
}
