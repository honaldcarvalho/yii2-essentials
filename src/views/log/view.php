<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Log */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Logs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

// Decodifica o campo data (diff salvo como JSON)
$diffHtml = '';
if ($model->data) {
    $decoded = json_decode($model->data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        // Exibe tabela só se houver "changes"
        if (!empty($decoded['changes'])) {
            $diffHtml .= '<table class="table table-bordered table-sm">';
            $diffHtml .= '<thead><tr>'
                . '<th>' . Yii::t('app', 'Attribute') . '</th>'
                . '<th class="text-muted">' . Yii::t('app', 'From') . '</th>'
                . '<th class="text-success">' . Yii::t('app', 'To') . '</th>'
                . '</tr></thead><tbody>';

            foreach ($decoded['changes'] as $row) {
                $diffHtml .= '<tr>'
                    . '<td><code>' . Html::encode($row['attr'] ?? '') . '</code></td>'
                    . '<td class="text-muted">' . Html::encode($row['from'] ?? '') . '</td>'
                    . '<td class="text-success">' . Html::encode($row['to'] ?? '') . '</td>'
                    . '</tr>';
            }
            $diffHtml .= '</tbody></table>';
        } else {
            $diffHtml = Html::tag('pre', Html::encode(print_r($decoded, true)));
        }
    } else {
        $diffHtml = Html::tag('pre', Html::encode($model->data));
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <p>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]) ?>
        </p>
        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id',
                [
                    'attribute' => 'user.username', // corrigido usernamer → username
                    'label' => Yii::t('app', 'User'),
                ],
                'controller',
                'action',
                [
                    'attribute' => 'data',
                    'format'    => 'raw',
                    'value'     => $diffHtml,
                ],
                'created_at:datetime',
            ],
        ]) ?>
    </div>
</div>
