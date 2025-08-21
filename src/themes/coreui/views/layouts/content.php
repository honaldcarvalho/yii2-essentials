<?php

use yii\bootstrap5\Alert;
?>

<div class="wrapper d-flex flex-column min-vh-100">
    <div class="body flex-grow-1">
        <?= Alert::widget(); ?>
        <?= $content; ?>
    </div>
</div>