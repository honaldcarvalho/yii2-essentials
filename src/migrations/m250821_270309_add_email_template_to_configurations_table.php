<?php

use yii\db\Migration;

class m250821_270309_add_email_template_to_configurations_table extends Migration
{

    public function safeUp()
    {
        $message_noreply = Yii::t('app', 'Please do not reply to this email. This mailbox is not monitored and you will not receive a response.');
        $html = <<< HTML
        <!doctype html>
        <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <style>
            body{background-color:#212631;font-family:sans-serif;font-size:14px;line-height:1.4;margin:0;padding:0;color:rgba(255,255,255,0.87)}
            table{border-collapse:separate;width:100%}
            table td{font-family:sans-serif;font-size:14px;vertical-align:top;color:rgba(255,255,255,0.87)}
            .container{display:block;margin:0 auto !important;max-width:580px;padding:10px;width:580px}
            .content{box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px}
            .main{background:#323a49;border-radius:6px;width:100%}
            .wrapper{box-sizing:border-box;padding:20px}
            h1,h2,h3,h4{color:#fff;margin:0 0 20px;font-weight:500;line-height:1.3}
            h1{font-size:24px;text-align:center}
            a{color:#198754;text-decoration:underline}
            .btn a{background-color:#198754;border:1px solid #198754;border-radius:5px;color:#fff !important;display:inline-block;font-size:14px;font-weight:bold;margin:0;padding:12px 25px;text-decoration:none;text-transform:capitalize}
            .btn a:hover{background-color:#157347;border-color:#157347}
            .footer{clear:both;margin-top:20px;text-align:center;width:100%}
            .footer p,.footer a{color:#6b7785;font-size:12px;text-align:center}
            @media only screen and (max-width:620px){.container{width:100% !important;padding:0 !important}.content{padding:0 !important}h1{font-size:22px !important}table.body p,table.body td,table.body a{font-size:16px !important}.btn a{width:100% !important;text-align:center !important}}
            </style>
            <title>{{subject}}</title>
        </head>
        <body>
            <span class="preheader"></span>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
            <tr>
                <td>&nbsp;</td>
                <td class="container">
                <div class="content">
                    <table role="presentation" class="main">
                    <tr>
                        <td style="padding:20px 20px 0;text-align:center">
                        <a href="{{company_host}}" target="_blank" rel="noopener" style="display:inline-block">
                            <img src="{{logo_url}}" alt="{{company_title}} logo" style="border:none;width:150px;max-width:150px">
                        </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="wrapper" style="padding-top:10px">
                        <h1>{{subject}}</h1>
                        {{content}}
                        </td>
                    </tr>
                    </table>
                    <div class="footer">
                    <p><strong>{{company_title}}</strong> — {{company_slogan}}<br/>{{company_name}} · <a href="mailto:{{company_email}}" style="color:#6b7785">{{company_email}}</a> · {{company_host}}</p>
                    <p><small>{$message_noreply}</small></p>
                    </div>
                </div>
                </td>
                <td>&nbsp;</td>
            </tr>
            </table>
        </body>
        </html>
        HTML;
        $this->addColumn('configurations', 'email_template', $this->text()->defaultValue($html));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('configurations', 'email_template');
    }
}
