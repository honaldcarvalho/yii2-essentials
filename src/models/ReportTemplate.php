<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * ReportTemplate model
 *
 * @property int $id
 * @property int|null $group_id
 * @property string $name
 * @property string|null $description
 * @property string|null $header_html
 * @property string|null $footer_html
 * @property string $body_html
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 */
class ReportTemplate extends ModelCommon
{
    public static function tableName()
    {
        return '{{%report_templates}}';
    }

    public function rules()
    {
        return [
            [['name', 'body_html'], 'required'],
            [['description', 'header_html', 'footer_html', 'body_html'], 'string'],
            [['status', 'group_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'group_id' => Yii::t('app', 'Group'),
            'name' => Yii::t('app', 'Template Name'),
            'description' => Yii::t('app', 'Description'),
            'header_html' => Yii::t('app', 'Header HTML'),
            'footer_html' => Yii::t('app', 'Footer HTML'),
            'body_html' => Yii::t('app', 'Body HTML'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function init()
    {
        parent::init();

        if ($this->isNewRecord) {
            $this->style = <<<CSS
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 10pt;
                color: #212529;
            }
            h1, h2, h3 {
                font-weight: 600;
                margin: 0.5rem 0;
            }
            h2 {
                font-size: 16pt;
            }
            #body table {
                border-collapse: collapse;
                width: 100%;
                margin-top: 10px;
            }
            #body th, #body td {
                border: 1px solid #dee2e6;
                padding: 6px 8px;
                vertical-align: middle;
            }
            #body thead th {
                background-color:#287c36;
                color: #fff;
                text-align: center;
                font-weight: 600;
            }
            #body tbody tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .text-center { text-align: center; }
            .text-end   { text-align: right; }
            .fw-bold    { font-weight: bold; }
            .small      { font-size: 8pt; }
            CSS;

            $this->header_html = <<<HTML
            <div style="width: 100%; text-align: center; border-bottom: 1px solid #287c36;">
                <table width="100%"  style="border-width: 0px;">
                    <tr>
                        <td width="20%" style="text-align:left; border-width: 0px;">
                            <img src="https://croacworks.com.br/images/croacworks-logo-hq.png" height="60">
                        </td>
                        <td width="60%" style="text-align:center; border-width: 0px;">
                            <div style="font-size:24pt; font-weight:bolder; color:#287c36;"><strong>CroacWorks</strong></div>
                            <div style="font-size:10pt; font-weight:bolde; color:#666;"><strong>Saltando da ideia ao resultado com estilo e inovação.</strong></div>
                            <div style="font-size:9pt; color:#666;"><strong>CNPJ</strong> 07.481.906/0003-14</div>
                        </td>
                        <td width="20%" style="text-align:right; font-size:9pt; color:#666; border-width: 0px;">
                            <strong>Data:</strong> {{date}}<br><strong>Hora:</strong> {{time}}
                        </td>
                    </tr>
                </table>
            </div>
            HTML;

            $this->body_html = <<<HTML
            <div id="body" style="padding-left: 15mm; padding-right: 15mm;">
            <h1 style="text-align:center; color:#287c36;">Financial Report</h1>
            <p><strong>Period:</strong> {date_start} - {date_end}</p>
            <table border="1" width="100%" cellspacing="0" cellpadding="6">
                <thead style="background:#f5f5f5;">
                    <tr>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th style="text-align:right;">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-each="items">
                        <td>{patient_name}</td>
                        <td>{service_name}</td>
                        <td>{date}</td>
                        <td style="text-align:right;">{value}</td>
                    </tr>
                </tbody>
            </table>
            <p style="text-align:right; margin-top:20px;"><strong>Total:</strong> {total}</p>
            </div>
            HTML;

            $this->footer_html = <<<HTML
            <div style="width:100%; border-top:1px solid #287c36; padding-top:5px; font-size:9pt; text-align:center; color:#666;">
                <strong>CroacWorks</strong> — Relatório gerado em {{date}} às {{time}} — Página {PAGENO} de {nbpg}
            </div>
            HTML;
        }
    }

}


/** 
 * Example of use
 * 
 * 
 * UNIQUE DADTA
 public function actionRenderReport($id, $template_id)
{
    $model = $this->findModel($id, ServiceOrder::class);
    $template = \app\models\ReportTemplate::findOne($template_id);

    if (!$template) {
        throw new NotFoundHttpException("Template não encontrado");
    }

    // Substituição de placeholders no body_html
    $body = strtr($template->body_html, [
        '{patient_name}' => $model->patient->fullname ?? '',
        '{order_id}' => $model->id,
        '{date}' => Yii::$app->formatter->asDate($model->created_at),
        '{total}' => Yii::$app->formatter->asCurrency($model->total),
    ]);

    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_top' => 30,
        'margin_bottom' => 20,
        'default_font' => 'dejavusans',
    ]);

    if ($template->header_html) {
        $mpdf->SetHeader($template->header_html);
    }
    if ($template->footer_html) {
        $mpdf->SetFooter($template->footer_html);
    }

    $mpdf->WriteHTML($body);
    return $mpdf->Output("Report_{$model->id}.pdf", \Mpdf\Output\Destination::INLINE);
}

/** MUTIPLE DATA


public function actionFinancialReport($template_id)
{
    $items = ServiceOrderItem::find()
        ->joinWith(['serviceOrder.patient', 'service'])
        ->all();

    $template = ReportTemplate::findOne($template_id);
    if (!$template) {
        throw new NotFoundHttpException("Template não encontrado");
    }

    $data = [
        'date_start' => '01/10/2025',
        'date_end'   => '04/10/2025',
        'total'      => Yii::$app->formatter->asCurrency(array_sum(array_map(fn($i) => $i->price, $items))),
        'items'      => array_map(function($i) {
            return [
                'patient_name' => $i->serviceOrder->patient->fullname ?? '',
                'service_name' => $i->service->name ?? '',
                'date'         => Yii::$app->formatter->asDate($i->created_at),
                'value'        => Yii::$app->formatter->asCurrency($i->price),
            ];
        }, $items),
    ];

    $html = ReportTemplateHelper::render($template->body_html, $data);

    $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
    if ($template->header_html) {
        $mpdf->SetHeader($template->header_html);
    }
    if ($template->footer_html) {
        $mpdf->SetFooter($template->footer_html);
    }

    $mpdf->WriteHTML($html);
    return $mpdf->Output("Relatorio_Financeiro.pdf", \Mpdf\Output\Destination::INLINE);
}

On view create/update:

<?= $form->field($model, 'body_html')->widget(\dosamigos\tinymce\TinyMce::class, [
    'options' => ['rows' => 20],
    'language' => 'pt_BR',
    'clientOptions' => [
        'plugins' => [
            "advlist autolink lists link charmap print preview anchor",
            "searchreplace visualblocks code fullscreen",
            "insertdatetime media table paste code help wordcount"
        ],
        'toolbar' => "undo redo | formatselect | bold italic | 
                      alignleft aligncenter alignright alignjustify | 
                      bullist numlist outdent indent | removeformat | help"
    ]
]); ?>

Body template:

<h2>Relatório Financeiro</h2>
<p>Período: {date_start} até {date_end}</p>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Paciente</th>
            <th>Serviço</th>
            <th>Data</th>
            <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        {{#each items}}
        <tr>
            <td>{patient_name}</td>
            <td>{service_name}</td>
            <td>{date}</td>
            <td>{value}</td>
        </tr>
        {{/each}}
    </tbody>
</table>

<p><b>Total:</b> {total}</p>

**/