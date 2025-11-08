<?php

namespace croacworks\essentials\controllers;

use Yii;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\symfonymailer\Mailer;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use croacworks\essentials\controllers\rest\StorageController;
use croacworks\essentials\helpers\ModelHelper;
use croacworks\essentials\helpers\TranslatorHelper;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\File;
use croacworks\essentials\models\ModelCommon;

use yii\helpers\Url;

/**
 * Description of Controller
 *
 * @author Honald Carvalho
 * 
 * 
 */

class CommonController extends \yii\web\Controller
{

    public  $guest  = [];
    public  $free   = [];
    private $fixed  = [];
    public $access = [];
    public $config = null;
    public $configuration = null;
    static $assetsDir;

    public static function getClassPath()
    {
        return get_called_class();
    }

    public static function getPath()
    {
        $path_parts = explode("\\", self::getClassPath());
        if (count($path_parts) == 4)
            return "{$path_parts[0]}/{$path_parts[2]}";

        return strtoupper($path_parts[0]);
    }

    public static function getAssetsDir()
    {
        return Yii::$app->assetManager->getPublishedUrl('@vendor/croacworks/yii2-essentials/src/themes/coreui/web');
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $language = null;
        $this->configuration = Configuration::get();
        $this->config = Configuration::get();

        foreach ($this->config->attributes as $key => $param) {
            Yii::setAlias("@{$key}", "$param");
        }
        $params = $this->configuration->getParameters()->all();

        foreach ($params as $key => $param) {
            Yii::setAlias("@{$param->name}", "{$param->value}");
            Yii::setAlias("@{$param->name}_description", "{$param->description}");
        }

        if (!\Yii::$app->user->isGuest) {
            $language = \Yii::$app->user->identity->profile->language->code;
        }

        $hostName = Yii::$app->request->hostInfo;
        if (empty($hostName)) {
            $hostName = Yii::$app->request->hostName;
        }

        $byHost = Configuration::find()->where(['host' => $hostName])->one();

        if ($byHost) {
            $this->config = $byHost;
        }

        \Yii::$app->language = $language ?? $this->config->language->code ?? 'en-US';

        return $behaviors;
    }

    static function classExist($modelClass)
    {
        $modelClassCommon = '\\croacworks\\essentials\\models\\' . $modelClass;
        $modelClassApp = '\\app\\models\\' . $modelClass;

        if (class_exists($modelClassCommon)) {
            return $modelClassCommon;
        } else if (class_exists($modelClassApp)) {
            return $modelClassApp;
        }
        return null;
    }

    public function actionClearCache($cacheKey)
    {
        return ModelCommon::clearCacheCustom($cacheKey);
    }

    public function actionGetFields($class)
    {
        return $this->asJson(['results' => ModelHelper::getFields($class)]);
    }

    public function actionStatus($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $model->scenario = $model::SCENARIO_STATUS;
        $model->status == 0 ? $model->status = 1 : $model->status = 0;
        $model->save();
        return ['success' => $model->save(), 'result' => $model->getErrors()];
    }

    public static function listModels()
    {
        $modelsPath = __DIR__ . '/../models'; // Caminho para o diretório de modelos
        $files = scandir($modelsPath);
        $models = [];
        foreach ($files as $file) {
            if (is_file($modelsPath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = pathinfo($file, PATHINFO_FILENAME);
                $models[$className] = $className;
            }
        }
        return $models;
    }

    public function actionRemove($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $file = null;

        if ($model !== null) {
            if (method_exists($model, 'getFile')) {
                $file = $model->getFile()->one();
                if ($file !== null) {
                    StorageController::removeFile($file->id);
                }
            }
            if (method_exists($model, 'getFiles')) {
                $files = $model->getFiles()->all();
                if ($file !== null) {
                    StorageController::removeFile($file->id);
                }
                foreach ($files as $file) {
                    StorageController::removeFile($file->id);
                }
            }
            $result = $model->delete();
        }

        return ['success' => $result, 'result' => $model->getErrors()];
    }

    public function actionDelete($id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $this->goBack();
        if ($model !== null) {
            if (method_exists($model, 'getFile')) {
                $file = $model->getFile()->one();
                if ($file !== null) {
                    StorageController::removeFile($file->id);
                }
            }
            if (method_exists($model, 'getFiles')) {
                $files = $model->getFiles()->all();
                if ($file !== null) {
                    StorageController::removeFile($file->id);
                }
                foreach ($files as $file) {
                    StorageController::removeFile($file->id);
                }
            }
            $model->delete();
            return $this->redirect(['index']);
        }
        $this->goBack();
    }

    public function updateUpload($model, $post)
    {

        $old = $model->file_id;
        $changed = false;
        $post = Yii::$app->request->post();

        if ($model->load($post)) {

            $file = \yii\web\UploadedFile::getInstance($model, 'file_id');
            if (!empty($file) && $file !== null) {
                $file = StorageController::uploadFile($file, ['save' => true]);
                if ($file['success'] === true) {
                    $model->file_id = $file['data']['id'];
                    $changed = true;
                }
            } else if (isset($post['remove']) && $post['remove'] == 1) {
                $model->file_id = null;
                $changed = true;
            }

            if (!$changed) {
                $model->file_id = $old;
            }

            if ($model->save()) {
                if ($changed) {
                    StorageController::removeFile($old);
                }
                Yii::$app->getSession()->setFlash('success', Yii::t('app', 'File updated successfully!'));
                return true;
            }
        }
        Yii::$app->getSession()->setFlash('error', Yii::t('app', 'File not updated!'));
        return false;
    }

    public function actionClone($id)
    {

        // Busca o registro original
        $originalModel = $this->findModel($id);
        if (!$originalModel) {
            throw new NotFoundHttpException("Model with ID $id not found.");
        }

        $modelClass = get_class($originalModel);

        // Clona o model
        $clone = new $modelClass();
        $clone->attributes = $originalModel->attributes;
        $clone->setIsNewRecord(true);
        $clone->id = null;

        if ($clone->load(Yii::$app->request->post()) && $clone->save()) {
            return $this->redirect(['view', 'id' => $clone->id]);
        }

        // Renderiza a view update (ou clone se quiser separar)
        return $this->render('update', [
            'model' => $clone,
        ]);
    }

    /**
     * Generic translation suggestion endpoint.
     * Accepts a text and returns its automatic translation.
     *
     * @param string $language Target language code (e.g. 'pt', 'en', 'es', 'fr')
     * @return array JSON response
     */
    public function actionSuggestTranslation($language,$to='auto')
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $body = Yii::$app->request->getBodyParams();
        $text = $body['text'] ?? null;

        if (!$text) {
            return [
                'success' => false,
                'message' => Yii::t('app', 'Missing "text" parameter.')
            ];
        }

        try {
            // Use TranslatorHelper to translate the input text
            $translated = TranslatorHelper::translate(
                $text,
                $language,
                $to
            );

            return [
                'success' => true,
                'translation' => $translated,
            ];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => Yii::t('app', 'Error while suggesting translation.'),
                'error' => YII_DEBUG ? $e->getMessage() : null,
            ];
        }
    }

    // Generalized function to save or update a model
    public function actionSaveModel($modelClass)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $verClass = self::classExist($modelClass);
        if ($verClass === null) {
            return ['success' => false, 'message' => "Model class '$modelClass' does not exist."];
        } else {
            $modelClassNamespace = $verClass;
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post[$modelClass]['id']) && !empty($post[$modelClass]['id'])) {
                $model = $modelClassNamespace::findOne($post[$modelClass]['id']);
            } else {
                $model = new $modelClassNamespace();
            }

            if ($model->load($post)) {
                return ['success' => $model->save(), 'message' => $model->getErrors()];
            }
        }
        return ['success' => false];
    }

    // Generalized function to get a model by ID
    public function actionGetModel($modelClass, $id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $verClass = self::classExist($modelClass);
        if ($verClass === null) {
            return ['success' => false, 'message' => "Model class '$modelClass' does not exist."];
        } else {
            $modelClassNamespace = $verClass;
        }
        return $modelClassNamespace::findOne($id);
    }

    // Generalized function to get a models
    public function actionGetModels()
    {
        $items = [];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (\Yii::$app->request->isPost) {
            $post = \Yii::$app->request->post();
            $verClass = self::classExist($post['modelClass']);
            if ($verClass === null) {
                return ['success' => false, 'message' => "Model class '{$post['modelClass']}' does not exist."];
            } else {
                $modelClassNamespace = $verClass;
            }
            $items = $modelClassNamespace::find()->select("id,{$post['modelField']}")->where([$post['condition'], $post['modelField'], $post['value']])->limit(20)->all();
        }
        return $items;
    }

    public function actionCloneModel($modelClass, $id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $verClass = self::classExist($modelClass);
            if ($verClass === null) {
                return ['success' => false, 'message' => "Model class '$modelClass' does not exist."];
            }

            /** @var \yii\db\ActiveRecord $modelClassNamespace */
            $modelClassNamespace = $verClass;
            $originalModel = $modelClassNamespace::findOne($id);

            if ($originalModel === null) {
                return ['success' => false, 'message' => "Model with ID $id not found."];
            }

            // Clona o model e limpa o ID
            $newModel = new $modelClassNamespace();
            $newModel->attributes = $originalModel->attributes;
            $newModel->setIsNewRecord(true);
            $newModel->id = null;

            // Se o model tiver timestamps automáticos, eles serão atualizados no save
            if ($newModel->save()) {
                return ['success' => true, 'message' => 'Model cloned successfully.', 'id' => $newModel->id];
            } else {
                return ['success' => false, 'message' => 'Error saving cloned model.', 'errors' => $newModel->getErrors()];
            }
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    // Generalized function to delete a model by ID
    public function actionRemoveModel($modelClass, $id)
    {
        $verClass = self::classExist($modelClass);
        if ($verClass === null) {
            return ['success' => false, 'message' => "Model class '$modelClass' does not exist."];
        } else {
            $modelClassNamespace = $verClass;
        }
        return $modelClassNamespace::findOne($id)->delete();
    }

    public function actionOrderModel()
    {
        $items = [];
        $resuts = [];

        if (Yii::$app->request->isPost) {

            $post = \Yii::$app->request->post();
            $items = $post['items'];
            $field = $post['field'];

            $verClass = self::classExist($post['modelClass']);

            if ($verClass === null) {
                return \yii\helpers\Json::encode(['success' => false, 'message' => "Model class '{$post['modelClass']}' does not exist."]);
            } else {
                $modelClassNamespace = $verClass;
            }

            foreach ($items as $key => $value) {
                $model = $modelClassNamespace::find()->where(['id' => $value])->one();
                $model->{$field} =  $key + 1;
                $resuts[$value] = ['save' => $model->save(), 'model' => $model, 'key' => $key + 1];
            }
        }

        return \yii\helpers\Json::encode(['atualizado' => $resuts]);
    }

    public function actionStatusModel($modelClass, $id)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $verClass = self::classExist($modelClass);
        if ($verClass === null) {
            return ['success' => false, 'message' => "Model class '$modelClass' does not exist."];
        }

        $modelClassNamespace = $verClass;
        $model = $modelClassNamespace::findOne($id);

        if (!$model) {
            return ['success' => false, 'message' => "Model with ID '$id' not found."];
        }

        if (!property_exists($model, 'status')) {
            return ['success' => false, 'message' => "The model '$modelClass' does not have a 'status' property."];
        }

        $model->status = $model->status == 0 ? 1 : 0;

        if ($model->save()) {
            return ['success' => true, 'status' => $model->status];
        }

        return ['success' => false, 'errors' => $model->getErrors()];
    }

    public function actionExport()
    {
        $request = Yii::$app->request;
        $trigger = $request->get($this->exportTrigger);

        if (in_array($trigger, $this->formats, true)) {
            Yii::$app->controller->layout = '_blank';
            Yii::$app->response->format = Response::FORMAT_RAW;
            $filename = $request->get('filename', $this->filename);

            $data = [];
            $columnKeys = [];
            $columnLabels = [];

            foreach ($this->dataProvider->getModels() as $model) {
                $row = [];
                foreach ($this->columns as $column) {
                    $attribute = null;
                    $value = null;
                    $label = null;

                    if (is_array($column)) {
                        $attribute = $column['attribute'] ?? null;
                        $value = $column['value'] ?? null;
                        $label = $column['label'] ?? $attribute;
                    } elseif (is_string($column)) {
                        $parts = explode(':', $column);
                        $attribute = $parts[0] ?? null;
                        $label = $parts[2] ?? $attribute;
                    }

                    if ($attribute) {
                        $columnKeys[] = $attribute;
                        $columnLabels[$attribute] = $label;
                        if (is_callable($value)) {
                            $row[$attribute] = call_user_func($value, $model);
                        } else {
                            $row[$attribute] = ArrayHelper::getValue($model, $attribute);
                        }
                    }
                }
                $data[] = $row;
            }

            $columnKeys = array_values(array_unique($columnKeys));

            if ($trigger === 'pdf') {
                $html = '<h2>' . Html::encode($filename) . '</h2><table border="1" cellpadding="5"><thead><tr>';
                foreach ($columnKeys as $header) {
                    $html .= '<th>' . Html::encode($columnLabels[$header] ?? $header) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= '<tr>';
                    foreach ($columnKeys as $key) {
                        $html .= '<td>' . Html::encode($row[$key] ?? '') . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';

                return $html;
            }

            if ($trigger === 'csv') {
                header("Content-Type: text/csv");
                header("Content-Disposition: attachment; filename={$filename}.csv");
                $output = fopen('php://output', 'w');
                fputcsv($output, array_map(fn($key) => $columnLabels[$key] ?? $key, $columnKeys));
                foreach ($data as $row) {
                    $line = [];
                    foreach ($columnKeys as $key) {
                        $line[] = $row[$key] ?? '';
                    }
                    fputcsv($output, $line);
                }
                fclose($output);
                return;
            }

            if ($trigger === 'excel') {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->fromArray([array_map(fn($key) => $columnLabels[$key] ?? $key, $columnKeys)], NULL, 'A1');
                foreach ($data as $i => $row) {
                    $line = [];
                    foreach ($columnKeys as $key) {
                        $line[] = $row[$key] ?? '';
                    }
                    $sheet->fromArray([$line], NULL, 'A' . ($i + 2));
                }
                $writer = new Xlsx($spreadsheet);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                return;
            }
        }

        // Render botões
        $buttons = [];
        foreach ($this->formats as $format) {
            $url = Url::current([
                $this->exportTrigger => $format,
                'filename' => $this->filename,
            ]);
            $buttons[] = Html::a(
                $this->labelMap[$format] ?? strtoupper($format),
                $url,
                ['class' => 'btn btn-outline-secondary me-2']
            );
        }

        return Html::tag('div', implode("\n", $buttons), ['class' => 'export-button-group']);
    }

    public static function error($th)
    {
        if (isset($th->statusCode)) {
            if ($th->statusCode == 400) {
                throw new \yii\web\BadRequestHttpException($th->getMessage());
            } else if ($th->statusCode == 401) {
                throw new \yii\web\MethodNotAllowedHttpException($th->getMessage());
            } else if ($th->statusCode == 403) {
                throw new \yii\web\ForbiddenHttpException($th->getMessage());
            } else if ($th->statusCode == 404) {
                throw new \yii\web\NotFoundHttpException($th->getMessage());
            }
        }
        throw new \yii\web\ServerErrorHttpException(Yii::t('app', $th->getMessage()));
    }

    public static function customControllersUrl($controllers, $folder = 'custom')
    {
        $rules = [];
        foreach ($controllers as $key => $controller) {
            $rules["{$controller}/<id:\d+>"] = "{$folder}/{$controller}/view";
            $rules["{$controller}/<action>/<id:\d+>"] = "{$folder}/{$controller}/<action>";
            $rules["{$controller}/<action>"] = "{$folder}/{$controller}/<action>";
            $rules["{$controller}"] = "{$folder}/{$controller}";
        }
        return $rules;
    }

    static function addSlashUpperLower($string)
    {

        $split = str_split($string);
        $count = 0;
        $cut = 0;

        foreach ($split as $key => $value) {
            if (ctype_upper($value) && $count > 0) {
                $cut = $key;
            }
            $count++;
        }

        $first = strtolower(substr($string, 0, $cut));
        $second = strtolower(substr($string, $cut));

        if (!empty($first))
            return "{$first}-{$second}";

        return false;
    }

    /*** FUNÇÕES UTILITARIAS ***/

    function actionPhpInfo()
    {
        phpinfo();
    }

    function getOS()
    {

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $os_platform =   "Unknown";
        $os_array =   array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }
        }
        return $os_platform;
    }

    /**
     * Kullanicinin kullandigi internet tarayici bilgisini alir.
     * 
     * @since 2.0
     */
    function getBrowser()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $browser        = "Bilinmeyen Tarayıcı";
        $browser_array  = array(
            '/msie/i'       =>  'Internet Explorer',
            '/firefox/i'    =>  'Firefox',
            '/safari/i'     =>  'Safari',
            '/chrome/i'     =>  'Chrome',
            '/edge/i'       =>  'Edge',
            '/opera/i'      =>  'Opera',
            '/netscape/i'   =>  'Netscape',
            '/maxthon/i'    =>  'Maxthon',
            '/konqueror/i'  =>  'Konqueror',
            '/mobile/i'     =>  'Handheld Browser'
        );

        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser = $value;
            }
        }
        return $browser;
    }

    static function mailer()
    {

        $params = Configuration::get();
        $mailer = new Mailer();
        $model = $params->emailService;

        $mailer->transport = [
            'scheme' => $model->scheme,
            'host' => $model->host,
            'username' => $model->username,
            'password' => $model->password,
            'port' => $model->port,
            'enableMailerLogging' => true
            //'dsn' => "{$model->scheme}://{$model->username}:{$model->password}@{$model->host}:{$model->port}"
        ];

        return $mailer;
    }

    public function sendEmail($name, $from, $to, $subject, $message, $layout = '@vendor/croacworks/yii2-essentials/email/layouts/template')
    {

        $URL = Yii::$app->params['rootUrl'];
        $mail = Yii::$app->mailer->compose($layout, ['subject' => $subject, 'content' => $message])
            ->setFrom($from)
            ->setTo($to)
            ->setBcc('honald.silva@piauiconectado.com.br')
            ->setSubject($subject);
        //->setHtmlBody($message);

        if ($mail->send()) {
            return "email enviado";
        } else {
            return "email não enviado";
        }
    }

    public function sendEmailHtml($name, $from, $to, $subject, $message)
    {

        $URL = Yii::$app->params['rootUrl'];
        $mail = Yii::$app->mailer->compose()
            ->setFrom($from)
            ->setTo($to)
            ->setBcc('honaldcarvalhoa@gmail.com')
            ->setSubject($subject)
            ->setHtmlBody($message);

        if ($mail->send()) {
            return "email enviado";
        } else {
            return "email não enviado";
        }
    }

    public function sanitizeString($str)
    {
        $str = preg_replace('/[áàãâä]/ui', 'a', $str);
        $str = preg_replace('/[éèêë]/ui', 'e', $str);
        $str = preg_replace('/[íìîï]/ui', 'i', $str);
        $str = preg_replace('/[óòõôö]/ui', 'o', $str);
        $str = preg_replace('/[úùûü]/ui', 'u', $str);
        $str = preg_replace('/[ç]/ui', 'c', $str);
        $str = $this->sanatizeReplace($str);
        $str = preg_replace('/[^a-z0-9]/i', '_', $str);
        $str = preg_replace('/_+/', '-', $str);
        return $str;
    }

    public static function sanatize($str)
    {
        $removeItens = ["[", "]", ",", "(", ")", ";", ":", "|", "!", "\"", "$", "%", "&", "#", "=", "?", "~", ">", "<", "ª", "º", "-", ".", "\/", " "];
        foreach ($removeItens as $item) {
            $str = preg_replace('/[' . $item . ']/', '', $str);
        }
        return $str;
    }

    public static function sanatizeReplaced($str, $replace)
    {
        $str = preg_replace('/[áàãâä]/ui', 'a', $str);
        $str = preg_replace('/[éèêë]/ui', 'e', $str);
        $str = preg_replace('/[íìîï]/ui', 'i', $str);
        $str = preg_replace('/[óòõôö]/ui', 'o', $str);
        $str = preg_replace('/[úùûü]/ui', 'u', $str);
        $str = preg_replace('/[ç]/ui', 'c', $str);
        $str = preg_replace('/[^a-z0-9]/i', '_', $str);
        $str = preg_replace('/_+/', '-', $str);
        $removeItens = ["[", "]", ",", "(", ")", ";", ":", "|", "!", "\"", "$", "%", "&", "#", "=", "?", "~", ">", "<", "ª", "º", "-", ".", "\/", " "];
        foreach ($removeItens as $item) {
            $str = preg_replace('/[' . $item . ']/', $replace, $str);
        }
        return $str;
    }

    public function sanatizeReplace($str)
    {
        $removeItens = ["[", "]", ",", "(", ")", ";", ":", "|", "!", "\"", "$", "%", "&", "#", "=", "?", "~", ">", "<", "ª", "º", "-"];
        $str = preg_replace("#[/]#", '_', $str);
        foreach ($removeItens as $item) {
            $str = preg_replace('/[' . $item . ']/', '_', $str);
        }
        return $str;
    }

    public function sanatizeNoReplace($str)
    {
        $removeItens = ["[", "]", ",", "(", ")", ";", ":", "|", "!", "\"", "$", "%", "&", "#", "=", "?", "~", ">", "<", "ª", "º", "-"];
        $str = preg_replace("#[/]#", '_', $str);
        foreach ($removeItens as $item) {
            $str = preg_replace('/[' . $item . ']/', '', $str);
        }
        return $str;
    }

    public function formatBytes($bytes, $precision = 2, $show_unit = true)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        // $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 
        if ($show_unit) {
            return round($bytes, $precision) . ' ' . $units[$pow];
        } else {
            return ['value' => round($bytes, $precision), 'unit' => $units[$pow]];
        }
    }

    public static function getUserIP()
    {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

    static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function dataDiff($date_begin, $date_end)
    {
        $origin = strtotime($date_begin);
        $target = strtotime($date_end);
        $diff = $target - $origin;
        return $diff;
    }

    static function remove_emoji($string)
    {
        // Match Enclosed Alphanumeric Supplement
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
        $clear_string = preg_replace($regex_alphanumeric, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        // Match Supplemental Symbols and Pictographs
        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
        $clear_string = preg_replace($regex_supplemental, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }

    static function stripEmojis($string)
    {
        // Convert question marks to a special thing so that we can remove
        // question marks later without any problems.
        $string = str_replace("?", "{%}", $string);
        // Convert the text into UTF-8.
        $string = mb_convert_encoding($string, "ISO-8859-1", "UTF-8");
        // Convert the text to ASCII.
        $string = mb_convert_encoding($string, "UTF-8", "ISO-8859-1");
        // Replace anything that is a question mark (left over from the conversion.
        $string = preg_replace('/(\s?\?\s?)/', ' ', $string);
        // Put back the .
        $string = str_replace("{%}", "?", $string);
        // Trim and return.
        return trim($string);
    }

    /**
     * Converte uma imagem (arquivo local ou URL) em data URI base64.
     * Limite de 2MB para evitar e-mails gigantes.
     */
    public static function imageToDataUri(string $pathOrUrl, int $maxBytes = 2_000_000): ?string
    {
        try {
            $bytes = null;
            $mime  = null;

            // Tenta tratar como caminho local primeiro
            $localPath = static::toLocalPathIfPossible($pathOrUrl);
            if ($localPath && is_file($localPath)) {
                if (filesize($localPath) > $maxBytes) return null;
                $bytes = @file_get_contents($localPath);
                if ($bytes === false) return null;

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = $finfo ? finfo_file($finfo, $localPath) : null;
                if ($finfo) finfo_close($finfo);
            } else {
                // Trata como URL (se allow_url_fopen estiver habilitado)
                if (!preg_match('~^https?://~i', $pathOrUrl)) return null;
                $bytes = @file_get_contents($pathOrUrl);
                if ($bytes === false) return null;
                if (strlen($bytes) > $maxBytes) return null;

                // Descobre MIME a partir do conteúdo
                if (function_exists('getimagesizefromstring')) {
                    $info = @getimagesizefromstring($bytes);
                    $mime = $info['mime'] ?? null;
                }
                if (!$mime) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = $finfo ? finfo_buffer($finfo, $bytes) : null;
                    if ($finfo) finfo_close($finfo);
                }
            }

            if (!$mime) $mime = 'image/png'; // fallback
            $b64 = base64_encode($bytes);
            return 'data:' . $mime . ';base64,' . $b64;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Se o caminho for tipo "/uploads/logo.png" tenta resolvê-lo para o filesystem local.
     * Retorna null se parecer uma URL absoluta ou não for resolvível.
     */
    public static function toLocalPathIfPossible(string $path): ?string
    {
        // Já é URL http(s)
        if (preg_match('~^https?://~i', $path)) {
            return null;
        }

        // Caminho absoluto no FS
        if (is_file($path)) {
            return $path;
        }

        // Tenta @webroot (ex.: /var/www/app/web)
        $webroot = Yii::getAlias('@webroot', false);
        if ($webroot) {
            // se começar com "/", trata como absoluto relativo ao webroot
            if (substr($path, 0, 1) === '/') {
                $fs = rtrim($webroot, DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $path);
                if (is_file($fs)) return $fs;
            }
            // ou relativo simples
            $fs = rtrim($webroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (is_file($fs)) return $fs;
        }

        // Tenta @app/web (alguns projetos não têm @webroot definido em console)
        $appWeb = Yii::getAlias('@app/web', false);
        if ($appWeb) {
            if (substr($path, 0, 1) === '/') {
                $fs = rtrim($appWeb, DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $path);
                if (is_file($fs)) return $fs;
            }
            $fs = rtrim($appWeb, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (is_file($fs)) return $fs;
        }

        return null;
    }

    /**
     * Resolve a logo configurada e retorna SEMPRE um data URI (base64).
     * Se falhar, usa logo padrão remota (convertida para base64) ou um PNG 1x1.
     */
    public static function resolveLogoDataUri(?File $file): string
    {
        // 1) Tenta arquivo configurado
        if ($file && $file->path) {
            $data = static::imageToDataUri((string)$file->path);
            if ($data) return $data;
        }

        // 2) Tenta URL padrão da CroacWorks
        $fallbackUrl = 'https://croacworks.com.br/images/croacworks-logo-hq.png';
        $data = static::imageToDataUri($fallbackUrl);
        if ($data) {
            return $data;
        }
        // 3) PNG transparente 1x1 (fallback mínimo)
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
    }
}
