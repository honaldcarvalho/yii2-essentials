<?php

$enviroments = enviroments();
return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host='. $enviroments['DB_HOST'] .';dbname='. $enviroments['DB_NAME'] .';port='. $enviroments['DB_PORT_INTERNAL'] .';',
    'username' => 'root',
    'password' => $enviroments['DB_ROOT_PASSWORD'],
    'charset' => 'utf8',
    // Schema cache options [for production environment]
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
