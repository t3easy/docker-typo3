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
    'cache_hash' => 1 * $oneDay,
    'cache_imagesizes' => 0,
    'cache_pages' => 1 * $oneDay,
    'cache_pagesection' => 1 * $oneDay,
    'cache_rootline' => 1 * $oneDay,
    'extbase_reflection' => 0,
    'extbase_datamapfactory_datamap' => 0
];

if (($redisHost = getenv('REDIS_HOST')) && extension_loaded('redis')) {
    $redisPort = getenv('REDIS_PORT');
    $counter = 3;
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
            'database' => $counter++,
            'hostname' => $redisHost,
            'port' => $redisPort !== false ? (int)$redisPort : 6379,
            'defaultLifetime' => $defaultLifetime
        ];
    }
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'] = [
        'BE' => [
            'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
            'options' => [
                'hostname' => $redisHost,
                'database' => $counter++,
                'port' => $redisPort !== false ? (int)$redisPort : 6379,
            ],
        ],
        'FE' => [
            'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
            'options' => [
                'hostname' => $redisHost,
                'database' => $counter++,
                'port' => $redisPort !== false ? (int)$redisPort : 6379,
            ],
        ],
    ];
} elseif (($isApcuLoaded = extension_loaded('apcu') || extension_loaded('apc')) && ini_get('apc.enabled')) {
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] =
            $isApcuLoaded ? \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class : \TYPO3\CMS\Core\Cache\Backend\ApcBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
            'defaultLifetime' => $defaultLifetime,
        ];
    }
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
