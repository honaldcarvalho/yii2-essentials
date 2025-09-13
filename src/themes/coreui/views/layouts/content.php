<?php

use croacworks\essentials\widgets\Alert;
?>

<div class="wrapper d-flex flex-column min-vh-100">
    <?= $this->render('header', ['assetDir' => $assetDir, 'theme' => $theme, 'config' => $config]) ?>
    <div class="body flex-grow-1">
        <?= Alert::widget(); ?>
        <div class="container-xxl px-4">
            <?= $content; ?>
        </div>
    </div>
</div>