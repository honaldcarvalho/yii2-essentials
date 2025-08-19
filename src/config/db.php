<?php

$enviroments = enviroments();
return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host='. $enviroments['DB_HOST'] .';dbname='. $enviroments['DB_NAME'] .';port='. $enviroments['DB_PORT'] .';',
    'username' => $enviroments['DB_USER'],
    'password' => $enviroments['DB_PASSWORD'],
    'charset' => 'utf8',
    // Schema cache options [for production environment]
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
