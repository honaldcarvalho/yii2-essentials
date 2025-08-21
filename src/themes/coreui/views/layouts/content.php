<?php

use yii\bootstrap5\Alert;
?>

<div class="wrapper d-flex flex-column min-vh-100">
    <?= $this->render('header', ['assetDir' => $assetDir, 'theme' => $theme, 'configuration' => $configuration]) ?>
    <div class="body flex-grow-1">
        <?= Alert::widget(); ?>
        <div class="container-xxl px-0 mx-0">
            <?= $content; ?>
        </div>
    </div>
</div>