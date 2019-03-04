<?php

$context = \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext();
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
        ['charset' => 'utf8', 'driver' => 'mysqli', 'port' => 3306, 'unix_socket' => ''],
        $mysql
    );
}

if (($smtpServer = getenv('SMTP_SERVER')) && ($smtpPort = getenv('SMTP_PORT'))) {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = $smtpServer . ':' . $smtpPort;
}

$caches = [
    'cache_hash' => 86400,
    'cache_imagesizes' => 0,
    'cache_pages' => 86400,
    'cache_pagesection' => 86400,
    'cache_rootline' => 86400,
    'extbase_reflection' => 0,
    'extbase_datamapfactory_datamap' => 0
];

if (($redisHost = getenv('REDIS_HOST')) && extension_loaded('redis')) {
    $counter = 3;
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
            'database' => $counter++,
            'hostname' => $redisHost,
            'port' => ($redisPort = getenv('REDIS_PORT') ? (int)$redisPort : 6379),
            'defaultLifetime' => $defaultLifetime
        ];
    }
} elseif ((($isApc = extension_loaded('apc')) || extension_loaded('apcu')) && ini_get('apc.enabled')) {
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] =
            $isApc ? \TYPO3\CMS\Core\Cache\Backend\ApcBackend::class : \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend']['options'] = [
            'defaultLifetime' => $defaultLifetime
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
