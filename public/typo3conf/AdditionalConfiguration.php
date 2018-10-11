<?php

$context = \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext();
$isDocker = file_exists('/.dockerenv');

if (getenv('MYSQL_HOST') !== false) {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = [
        'charset' => 'utf8',
        'dbname' => getenv('MYSQL_DATABASE'),
        'driver' => 'mysqli',
        'host' => getenv('MYSQL_HOST'),
        'password' => getenv('MYSQL_PASSWORD'),
        'port' => 3306,
        'unix_socket' => '',
        'user' => getenv('MYSQL_USER')
    ];
}

if (getenv('SMTP_SERVER') !== false) {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = getenv('SMTP_SERVER') . ':' . getenv('SMTP_PORT');
}

if (getenv('REDIS_HOST') !== false && extension_loaded('redis')) {
    $caches = [
        'cache_hash' => 86400,
        'cache_imagesizes' => 0,
        'cache_pages' => 86400,
        'cache_pagesection' => 86400,
        'cache_rootline' => 86400,
        'extbase_reflection' => 0,
        'extbase_datamapfactory_datamap' => 0
    ];

    $counter = 3;
    foreach ($caches as $cache => $defaultLifetime) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['backend'] = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cache]['options'] = [
            'database' => $counter++,
            'hostname' => getenv('REDIS_HOST'),
            'port' => (getenv('REDIS_PORT') !== false ? getenv('REDIS_PORT') : 6379),
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
