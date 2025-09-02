<?php

use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Group;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\GroupSearch */
/* @var $form yii\widgets\ActiveForm */
$isMaster = AuthorizationController::isAdmin();

// Usuário atual
$user = AuthorizationController::User();

// Família do(s) grupo(s) do usuário
$familyIds = $user ? Group::familyIdsFromUser($user) : [];

// (Opcional) bypass admin: vê tudo
if (AuthorizationController::isAdmin()) {
    $familyIds = []; // lista vazia -> sem filtro
}

// (Opcional) impede escolher a si mesmo como pai
if ($model && $model->id) {
    $familyIds = array_diff($familyIds, [(int)$model->id]);
}

// Inclui o pai atual do registro (se existir) para não “sumir” ao editar
$currentParentId = $model && $model->parent_id ? (int)$model->parent_id : null;
if ($currentParentId) {
    $familyIds[] = $currentParentId;
}

// Normaliza IDs
$familyIds = array_values(array_unique(array_map('intval', $familyIds)));

$query = Group::find()->orderBy(['name' => SORT_ASC])->asArray();

// Aplica filtro somente se tiver familyIds e NÃO for admin
if (!AuthorizationController::isAdmin() && !empty($familyIds)) {
    $query->where(['id' => $familyIds]);
}

$parents = $query->all();


$labels = [
    'master' => Yii::t('app', 'Master'),
    'admin'  => Yii::t('app', 'Admin'),
    'user'   => Yii::t('app', 'User'),
    'free'   => Yii::t('app', 'Free'),
];

?>

<div class="row mt-2">
    <div class="col-md-12">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'parent_id')->dropDownList(
        ArrayHelper::map($parents, 'id', 'name'),
        [
            'prompt'   => $isMaster ? '' : Yii::t('app', 'Select a parent group'),
        ]
    )->label(Yii::t('app', 'Parent Group') . (!$isMaster ? ' *' : ''));
    ?>

    <?= $form->field($model, 'name') ?>

    <?php
    if ($isMaster) {
        // Master pode escolher qualquer nível
        echo $form->field($model, 'level')->dropDownList($labels, ['prompt' => '']);
    } else {
        // Não-master: só Admin e User
        $allowed = [
            'admin' => $labels['admin'],
            'user'  => $labels['user'],
        ];

        // Se estiver editando um registro cujo nível atual não é permitido,
        // mostramos o valor atual como somente leitura para evitar troca acidental.
        if (!$model->isNewRecord && isset($labels[$model->level]) && !isset($allowed[$model->level])) {
            echo $form->field($model, 'level')->textInput([
                'value'    => $labels[$model->level],
                'readonly' => true,
            ])->label(Yii::t('app', 'Level') . ' (' . Yii::t('app', 'read-only') . ')');
            echo Html::hiddenInput(Html::getInputName($model, 'level'), $model->level);
        } else {
            echo $form->field($model, 'level')->dropDownList($allowed, ['prompt' => '']);
        }
    }
    ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-search  mr-2"></i>' . Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('<i class="fas fa-broom mr-2"></i>' .Yii::t('app', 'Reset'), ['class' => 'btn btn-outline-secondary btn-reset']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <!--.col-md-12-->
</div>
