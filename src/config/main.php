<?php

require __DIR__ . '/enviroments.php';
require __DIR__ . '/urls_custom.php';
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$redis = require __DIR__ . '/redis.php';

$config = [
    'id' => 'app-basic',
    'name' => 'Basic Application',
    'language' => 'en-US',
    'sourceLanguage' => 'en-US',
    'basePath' => dirname(__DIR__),
    'layoutPath' => '@vendor/croacworks/yii2-essentials/src/themes/coreui/views/layouts',
    'controllerNamespace' => 'app\controllers',
    'bootstrap' => [
        'queue',
        'log'
    ],
    'runtimePath' => '@app/runtime',
    'modules' => [],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset'
    ],
    'modules' => [
        'common' => [
            'class' => '\croacworks\essentials\Module',
            'layoutPath' => '@vendor/croacworks/yii2-essentials/src/themes/coreui/views/layouts',
        ],
        'gridview' =>  [
            'class' => '\kartik\grid\Module',
            // enter optional module parameters below - only if you need to  
            // use your own export download action or custom translation 
            // message source
            'downloadAction' => 'gridview/export/download',
            // 'i18n' => []
        ]
    ],
    'components' => [
        'notify' => [
            'class' => \croacworks\essentials\services\Notify::class,
        ],
        'mutex' => [
            'class' => \yii\mutex\MysqlMutex::class,
            // 'db' => 'db',           // opcional (default 'db')
            'keyPrefix' => 'app_mutex_', // opcional
        ],
        // 'storage' => [
        //     'class' => \croacworks\essentials\components\StorageService::class,
        //     'driver' => [
        //         'class'   => \croacworks\essentials\components\storage\LocalStorageDriver::class,
        //         'basePath' => '@webroot/uploads',
        //         'baseUrl' => '@web/uploads',
        //     ],
        //     'defaultThumbSize' => 300,
        //     'enableQueue'      => true, // usa jobs se existir queue
        // ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue}}',
            'channel' => 'storage',
            'ttr' => 600,
            'attempts' => 2,
        ],
        'assetManager' => [
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => [],
                ],
                'kartik\form\ActiveFormAsset' => [
                    'bsDependencyEnabled' => false // do not load bootstrap assets for a specific asset bundle
                ],
            ],
            'forceCopy' => true
        ],
        'httpclient' => [
            'class' => 'yii\httpclient\Client',
            'baseUrl' => '/rest', // ajuste conforme necessário
        ],
        'session' => [
            'name' => 'template-backend-yii2',
            'timeout' => 2628000, //1 month in seconds
            'class' => 'yii\web\DbSession',
            'sessionTable' => 'yii_session',
        ],
        'view' => [
            // 'theme' => [
            //     'basePath' => '@vendor/croacworks/yii2-essentials/src/themes/coreui',
            //     'baseUrl' => '@vendor/croacworks/yii2-essentials/src/themes/coreui/web',
            //     'pathMap' => [
            //         '@app/views' => '@vendor/croacworks/yii2-essentials/src/themes/coreui/views',
            //     ],  
            // ],
        ],

        'formatter' => [
            'defaultTimeZone'    => 'America/Fortaleza',
            'dateFormat' => 'dd/MM/yyyy',
            'datetimeFormat' => 'php:d/m/y H:i',
            'timeFormat' => 'php:H:i',
            'decimalSeparator' => '.',
            'thousandSeparator' => '',
            'currencyCode' => 'BRL',
            'class' => 'croacworks\essentials\formatters\Custom',
            'numberFormatterOptions' => [
                \NumberFormatter::DECIMAL => \NumberFormatter::DEFAULT_STYLE, // Defina o estilo padrão
                \NumberFormatter::MIN_FRACTION_DIGITS => 2, // Define o número mínimo de casas decimais
                \NumberFormatter::MAX_FRACTION_DIGITS => 2, // Define o número máximo de casas decimais
            ],
        ],

        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\DbMessageSource',
                    'db' => 'db',
                    'sourceLanguage' => 'en-US',
                    'sourceMessageTable' => '{{%source_message}}',
                    'messageTable' => '{{%message}}',
                ],
                'app' => [
                    'class' => 'yii\i18n\DbMessageSource',
                    'db' => 'db',
                    'sourceLanguage' => 'en-US',
                    'sourceMessageTable' => '{{%source_message}}',
                    'messageTable' => '{{%message}}',
                ],
            ],
        ],

        'user' => [
            'identityClass' => '\croacworks\essentials\models\User',
            'enableAutoLogin' => false,
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => array_merge(
                customControllersUrl([
                    'dashboard',
                    'role',
                    'util',
                    'site',
                    'group',
                    'user',
                    'role',
                    'role-template',
                    'language',
                    'source-message',
                    'message',
                    'menu',
                    'params',
                    'configuration',
                    'meta-tag',
                    'email-service',
                    'license-type',
                    'license',
                    'log',
                    'folder',
                    'file',
                    'page-section',
                    'page',
                    'notification',
                    'notification-message',
                    'rest/mail',
                    'rest/storage',
                    'rest/auth',
                    'rest/address',
                    'rest/instagram',
                    'rest/youtube',
                    'rest/cron'
                ], 'common'),
                [
                    'GET /profile' => 'user/profile',
                    'f/<slug:[A-Za-z0-9]{8,64}>' => 'file/open',
                    'POST storage/upload' => 'storage/upload',
                    "site/clear-cache/<key:\w+>" => "site/clear-cache",

                    //--- REGRAS PAGE START ---//
                    'p/<group:\d+>/<section:[^/]+>/<lang:[A-Za-z0-9\-\_]+>/<slug:[A-Za-z0-9\-\_]+>' => 'common/page/public',

                    'p/<group:\d+>/<section:[^/]+>/<slug:[A-Za-z0-9\-\_]+>' => 'common/page/public',

                    'p/<group:\d+>/<lang:[A-Za-z0-9\-\_]+>/<slug:[A-Za-z0-9\-\_]+>' => 'common/page/public',

                    'p/<group:\d+>/<slug:[A-Za-z0-9\-\_]+>' => 'common/page/public',

                    // /p/<lang>/<slug>  -> group=1 por padrão
                    [
                        'pattern'  => 'p/<lang:[A-Za-z0-9\-\_]+>/<slug:[A-Za-z0-9\-\_]+>',
                        'route'    => 'common/page/public',
                        'defaults' => ['group' => 1],
                    ],

                    // /p/<slug> (se usar) -> group=1 por padrão
                    [
                        'pattern'  => 'p/<slug:[A-Za-z0-9\-\_]+>',
                        'route'    => 'common/page/public',
                        'defaults' => ['group' => 1],
                    ],
                    //--- REGRAS PAGE END ---//
                    
                    '<controller:\w+>/<id:\d+>' => '<controller>/view',
                    '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                    '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                    ['class' => 'yii\rest\UrlRule', 'controller' => 'tools'],
                ]
            ),
        ],

        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '4wq56d15s6d45as45a64d56asd456saX10-shd1wehcnbpac6651239018yhgkjce2982x',
            //'enableCookieValidation' => false,
            'enableCsrfValidation' => false,
            // 'csrfParam' => '_csrf-backend',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],

        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@app/runtime/cache',
        ],

        'errorHandler' => [
            'errorAction' => 'common/site/error',
        ],

        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'redis' => $redis,

    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1','*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
        // 'generators' => [ // here
        //     'crud' => [ // generator name
        //         'class' => 'yii\gii\generators\crud\Generator', // generator class
        //         'templates' => [ // setting for our templates
        //             'yii2-basics' => '@vendor/croacworks/yii2-essentials/src/gii/generators/crud/default' // template name => path to template
        //         ]
        //     ]
        // ]
    ];
}

return $config;
