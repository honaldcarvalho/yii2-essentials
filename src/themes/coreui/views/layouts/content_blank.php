<?php

use croacworks\essentials\widgets\Alert;
?>

<div class="wrapper d-flex flex-column min-vh-100">
    <div class="body flex-grow-1">
        <?= Alert::widget(); ?>
        <div class="container-xxl px-4">
            <?= $content; ?>
        </div>
    </div>
</div>