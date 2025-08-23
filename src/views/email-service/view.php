<?php

use croacworks\essentials\widgets\DefaultButtons;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\EmailService */

$this->title = "{$model->id}#{$model->description}";
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Email Services'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <?= croacworks\essentials\widgets\DefaultButtons::widget([
                            'controller' => Yii::$app->controller->id,
                            'model' => $model,
                            'verGroup' => false
                        ]) ?>
                        <?= Html::a(Yii::t('app', 'Test'), ['test', 'id' => $model->id], ['class' => 'btn btn-default']) ?>
                        <?= Html::a(
                            Yii::t('app', 'Visualizar Template'),
                            [
                                'preview',
                                'id' => $model->id,
                                'subject' => 'Reset Password — ' . Yii::$app->name,
                                'content' => '<p>Olá, Usuário</p><p>Siga o link abaixo para redefinir sua senha.</p>',
                            ],
                            [
                                'class' => 'btn btn-secondary',
                                'data-fancybox' => 'preview-template',
                                'data-type' => 'iframe',
                                'data-options' => json_encode([
                                    'iframe' => ['css' => ['width' => '100%', 'height' => '100%']],
                                    'toolbar' => true,
                                    'smallBtn' => true,
                                ]),
                            ]
                        ) ?>
                    </p>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'description',
                            'scheme',
                            'enable_encryption:boolean',
                            'encryption',
                            'host',
                            'password:password',
                            'username',
                            'port',
                        ],
                    ]) ?>
                </div>
                <!--.col-md-12-->
            </div>
            <!--.row-->
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>