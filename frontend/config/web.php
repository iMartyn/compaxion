<?php
$params = require(__DIR__ . '/params.php');
$config = [
	'id' => 'basic',
	'basePath' => dirname(__DIR__),
	'extensions' => require(__DIR__ . '/../vendor/yiisoft/extensions.php'),
	'components' => [
		'cache' => [
			'class' => 'yii\caching\FileCache',
		],
		'user' => [
			'identityClass' => 'app\models\User',
		],
		'errorHandler' => [
			'errorAction' => 'site/error',
		],
		'mail' => [
			'class' => 'yii\swiftmailer\Mailer',
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
	],
	'params' => $params,
];

if (YII_ENV_DEV) {
	$config['preload'][] = 'debug';
	$config['modules']['debug'] = 'yii\debug\Module';
	$config['modules']['gii'] = [
        'class'=>'yii\gii\Module',
        //'password'=>'P4ssw0rd!',
        //'ipFilters'=>false
    ];
}

return $config;
