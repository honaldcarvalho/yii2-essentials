<?php
$label =$model->menu->label ?? '';
$card = <<< CARD
    <tr class='col-sm-2 m-2 position-relative' data-key="{$model->id}" >
        <td>{$label}</td>
        <td>{$model->label}</td>
        <td>{$model->label}</td>
    </tr>
CARD;

echo $card;

?>
