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