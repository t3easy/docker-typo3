<?php

$context = \TYPO3\CMS\Core\Core\Environment::getContext();
$isDocker = file_exists('/.dockerenv');

if ($trustedHostsPattern = getenv('TRUSTED_HOSTS_PATTERN')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = $trustedHostsPattern;
}

$mysql = [
    'charset' => 'utf8mb4',
    'dbname' => getenv('MYSQL_DATABASE'),
    'driver' => 'mysqli',
    'host' => getenv('MYSQL_HOST'),
    'password' => getenv('MYSQL_PASSWORD'),
    'port' => 3306,
    'tableoptions' => [
        'charset' => 'utf8mb4',
        'collate' => 'utf8mb4_unicode_ci',
    ],
    'user' => getenv('MYSQL_USER'),
];
if ($mysql['dbname'] && $mysql['host'] && $mysql['password'] && $mysql['user']) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $mysql;
}

if (($smtpServer = getenv('SMTP_SERVER')) && ($smtpPort = getenv('SMTP_PORT'))) {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = $smtpServer . ':' . $smtpPort;
}

$oneDay = 86400;
$caches = [
    'extbase' => 0,
    'hash' => 1 * $oneDay,
    'imagesizes' => 0,
    'pages' => 1 * $oneDay,
    'pagesection' => 1 * $oneDay,
    'rootline' => 1 * $oneDay
];

$redisDatabase = 3;
$redisPort = 6379;

if ($redisCacheHostPrefix = getenv('REDIS_CACHE_HOST_PREFIX')) {
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
            'database' => $redisDatabase,
            'hostname' => $redisCacheHostPrefix . str_replace('_', '-', $cache),
            'port' => $redisPort,
            'defaultLifetime' => $defaultLifetime
        ];
    }
} else {
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] = \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
            'defaultLifetime' => $defaultLifetime,
        ];
    }
}

if ($redisSessionHostPrefix = getenv('REDIS_SESSION_HOST_PREFIX')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'] = [
        'BE' => [
            'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
            'options' => [
                'hostname' => $redisSessionHostPrefix . 'be',
                'database' => $redisDatabase,
                'port' => $redisPort,
            ],
        ],
        'FE' => [
            'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
            'options' => [
                'hostname' => $redisSessionHostPrefix . 'fe',
                'database' => $redisDatabase,
                'port' => $redisPort,
            ],
        ],
    ];
}

$GLOBALS['TYPO3_CONF_VARS']['BE']['versionNumberInFilename'] = 'embed';
$GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'] = 'embed';

if ($isDocker) {
    $GLOBALS['TYPO3_CONF_VARS']['GFX'] = array_merge(
        $GLOBALS['TYPO3_CONF_VARS']['GFX'],
        [
            'processor' => 'GraphicsMagick',
            'processor_allowTemporaryMasksAsPng' => false,
            'processor_colorspace' => 'RGB',
            'processor_effects' => false,
            'processor_enabled' => true,
            'processor_path' => '/usr/bin/',
            'processor_path_lzw' => '/usr/bin/',
        ]
    );
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
        \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
            \TYPO3\CMS\Core\Log\Writer\PhpErrorLogWriter::class => []
        ]
    ];
    unset($GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['Core']['Resource']['ResourceStorage']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::ERROR][\TYPO3\CMS\Core\Log\Writer\FileWriter::class]);
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['Core']['Resource']['ResourceStorage']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::ERROR][\TYPO3\CMS\Core\Log\Writer\PhpErrorLogWriter::class] = [];
}

if ($context->isDevelopment()) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::NOTICE][\TYPO3\CMS\Core\Log\Writer\FileWriter::class]['disabled'] = false;
}

if ($context->isTesting()){
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $cacheName => $cacheConfiguration) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
    }
}
