<?php

$enviroments = enviroments();
return [
    'class' => \yii\redis\Connection::class,
    'dsn' => 'mysql:host='. $enviroments['DB_HOST'] .';dbname=0;port=6379;',
    // Schema cache options [for production environment]
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
