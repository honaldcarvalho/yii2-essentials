<?php

use croacworks\essentials\models\Group;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use croacworks\essentials\controllers\AuthorizationController;
use yii\helpers\ArrayHelper;

/** @var yii\bootstrap5\ActiveForm $form */
/** @var croacworks\essentials\models\Group $model */

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

?>

<div class="group-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'parent_id')->dropDownList(
        ArrayHelper::map($parents, 'id', 'name'),
        ['prompt' => '']
    ); ?>

    <?= $form->field($model, 'level')->dropDownList([ 'master' => 'Master', 'admin' => 'Admin', 'user' => 'User', 'free' => 'Free', ]) ?>
    
    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-save mr-2"></i>'.Yii::t('app','Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
