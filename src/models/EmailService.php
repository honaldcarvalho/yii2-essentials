<?php

namespace croacworks\essentials\models;

use Yii;
use croacworks\essentials\controllers\AuthorizationController;
use yii\helpers\Url;
use yii\symfonymailer\Mailer;

/**
 * This is the model class for table "email_services".
 *
 * @property int $id
 * @property string $description
 * @property string $scheme
 * @property int|null $enable_encryption
 * @property string $encryption
 * @property string $host
 * @property string $username
 * @property string $password
 * @property string $template
 * @property int $port
 */
class EmailService extends ModelCommon
{
    public $verGroup = true;
    public const DEFAULT_LOGO_URL = 'https://croacworks.com.br/images/croacworks-logo-hq.png';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_services';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['enable_encryption', 'port'], 'integer'],
            [['host', 'username', 'password', 'port'], 'required', 'on' => ['create', 'update']],
            [['description', 'scheme', 'encryption', 'host', 'username', 'password'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'description' => Yii::t('app', 'Description'),
            'scheme' => Yii::t('app', 'Scheme'),
            'enable_encryption' => Yii::t('app', 'Enable Encryption'),
            'encryption' => Yii::t('app', 'Encryption'),
            'host' => Yii::t('app', 'Host'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'port' => Yii::t('app', 'Port'),
            'template' => Yii::t('app', 'Template'),
        ];
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($old_password = '')
    {
        $password_hash = md5($this->password);

        if (md5($old_password) != $password_hash) {
            try {
                $this->password = $password_hash;
                return true;
            } catch (\Throwable $th) {
                return false;
            }
        }
        return true;
    }

    /* ===============================
     * Helpers de Template / Placeholders
     * =============================== */

    /**
     * Default do template lido do schema da tabela, útil para registros antigos.
     */
    public static function getDbDefaultTemplate(): string
    {
        $schema = Yii::$app->db->schema->getTableSchema(static::tableName(), true);
        $col = $schema && isset($schema->columns['template']) ? $schema->columns['template'] : null;
        $default = $col ? $col->defaultValue : '';
        return (string)($default ?? '');
    }

    /**
     * Resolve URL do logo configurado com fallback.
     */
    public static function resolveLogoUrl(?Configuration $cfg): string
    {
        if ($cfg && $cfg->file && $cfg->file->path) {
            return Url::to($cfg->file->path, true);
        }
        return self::DEFAULT_LOGO_URL;
    }

    /**
     * Renderiza o template substituindo placeholders.
     * Placeholders:
     *  - {{logo_url}}
     *  - {{company_title}}, {{company_slogan}}, {{company_name}}, {{company_host}}, {{company_email}}
     *  - {{subject}}, {{content}}
     */
    public function renderTemplate(array $vars = []): string
    {
        $cfg = Configuration::get();

        // 1) Usa o template salvo no registro; 2) se vazio, usa default do DB; 3) se ainda vazio, string mínima
        $templateHtml = trim((string)$this->template);
        if ($templateHtml === '') {
            $templateHtml = static::getDbDefaultTemplate();
        }
        if ($templateHtml === '') {
            $templateHtml = '<html><body>{{content}}</body></html>';
        }

        $builtins = [
            '{{logo_url}}'       => self::resolveLogoUrl($cfg),
            '{{company_title}}'  => (string)($cfg->title ?? ''),
            '{{company_slogan}}' => (string)($cfg->slogan ?? ''),
            '{{company_name}}'   => (string)($cfg->bussiness_name ?? ''),
            '{{company_email}}'  => (string)($cfg->email ?? ''),
            '{{company_host}}'   => (string)($cfg->host ?? (Yii::$app->request->hostName ?? '')),
        ];

        $dynamic = [
            '{{subject}}' => (string)($vars['subject'] ?? ''),
            '{{content}}' => (string)($vars['content'] ?? ''),
        ];

        return strtr($templateHtml, array_merge($builtins, $dynamic));
    }

    /* ===============================
     * Envio usando o template do DB
     * =============================== */

    /**
     * Instancia um Mailer com o transport deste serviço.
     */
    public function buildMailer(): Mailer
    {
        $mailer = new Mailer();
        $mailer->transport = [
            'scheme' => $this->scheme,
            'host' => $this->host,
            'encryption' => $this->enable_encryption ? $this->encryption : '',
            'username' => $this->username,
            'password' => $this->password,
            'port' => $this->port,
            'enableMailerLogging' => true,
        ];
        return $mailer;
    }

    /**
     * Envia e-mail usando o template salvo no banco (ou o default da coluna).
     *
     * @param string|array $to email ou [email => nome]
     * @param string $subject
     * @param string $content HTML a injetar em {{content}}
     * @param array $options ['from' => '...', 'fromName' => '...', 'cc' => [...]]
     * @return array ['result'=>bool, 'message'=>string]
     */
    public function sendUsingTemplate($to, string $subject, string $content, array $options = []): array
    {
        $message_str = '';
        $mailer = $this->buildMailer();

        $cfg = Configuration::get();
        $fromEmail = $options['from'] ?? $this->username;
        $fromName  = $options['fromName'] ?? ($cfg->title . ' robot');

        $html = $this->renderTemplate([
            'subject' => $subject,
            'content' => $content,
        ]);

        $compose = $mailer->compose()
            ->setHtmlBody($html)
            ->setTo($to)
            ->setSubject($subject);

        if (!empty($fromEmail)) {
            $compose->setFrom([$fromEmail => $fromName]);
        }
        if (!empty($options['cc'])) {
            $compose->setCc($options['cc']);
        }

        $result = $compose->send();

        foreach (Yii::getLogger()->messages as $msg) {
            if (isset($msg[2]) && $msg[2] === 'yii\symfonymailer\Mailer::sendMessage') {
                $message_str .= $msg[2] . '|' . (is_string($msg[0]) ? $msg[0] : json_encode($msg[0])) . '/';
                Yii::$app->session->setFlash('error', 'Occoured some error: ' . (is_string($msg[0]) ? $msg[0] : json_encode($msg[0])));
            }
        }

        return ['result' => $result, 'message' => $message_str];
    }

    public static function sendEmail(
        $subject,
        $from_name,
        $to,
        $content,
        $cc = '',
        $from = ''
    ) {
        $service = EmailService::findOne(1); // ou conforme contexto
        return $service->sendUsingTemplate($to, $subject, $content, [
            'from' => $from,
            'fromName' => $from_name,
            'cc' => $cc
        ]);
    }

    public static function sendEmails(
        $subject,
        $from_email,
        $from_name,
        $to,
        $content
    ) {
        $service = EmailService::findOne(1); // ou conforme config
        return $service->sendUsingTemplate($to, $subject, $content, [
            'from' => $from_email,
            'fromName' => $from_name
        ]);
    }
}
