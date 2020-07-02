<?php

$context = \TYPO3\CMS\Core\Core\Environment::getContext();
$isDocker = file_exists('/.dockerenv');

if ($trustedHostsPattern = getenv('TRUSTED_HOSTS_PATTERN')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = $trustedHostsPattern;
}

$mysql = [
    'dbname' => getenv('MYSQL_DATABASE'),
    'host' => getenv('MYSQL_HOST'),
    'password' => getenv('MYSQL_PASSWORD'),
    'user' => getenv('MYSQL_USER')
];
if ($mysql['dbname'] && $mysql['host'] && $mysql['password'] && $mysql['user']) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = array_merge(
        [
            'charset' => 'utf8mb4',
            'driver' => 'mysqli',
            'port' => 3306,
            'tableoptions' => [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_unicode_ci',
            ],
        ],
        $mysql
    );
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

if ($context->isTesting()){
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $cacheName => $cacheConfiguration) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
    }
}
