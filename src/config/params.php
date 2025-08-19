<?php

Yii::setAlias('@uploadFolder', dirname(__DIR__).'/web/files');
if(isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST']))
Yii::setAlias('@rootUrl', "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}");

return [
    'adminEmail' => 'suporte@croacworks.com.br',
    'senderEmail' => 'noreply@croacworks.com.br',
    'senderName' => 'Example.com mailer',
    'bsVersion' => '5.x',
    'bsDependencyEnabled' => false,
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength'=>6,
    'upload.group' => false,
    'upload.folder' => 'files',
    'upload.quality' => 90,
    'upload.thumb.quality' => 90
];
