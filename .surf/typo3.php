<?php
/** @var \TYPO3\Surf\Domain\Model\Deployment $deployment */

$node = new \TYPO3\Surf\Domain\Model\Node('project.tld');
$node
    ->setHostname($node->getName())
    ->setOption('username', 'user')
    ->setOption('phpBinaryPathAndFilename', '/usr/local/bin/php_cli');

$application = new \TYPO3\Surf\Application\TYPO3\CMS();
$application
    ->addNode($node)
    ->setDeploymentPath('/httpdocs')
    ->setOption('repositoryUrl', 'file://' . dirname(__DIR__))
    ->setOption('keepReleases', 3)
    ->setOption('baseUrl', 'https://www.project.tld/')
    ->setOption('composerCommandPath', 'composer')
    ->setOption('webDirectory', 'public')
    ->setOption('symlinkDataFolders', ['fileadmin'])
    ->setOption('rsyncExcludes', [
        '.docker*',
        '.editorconfig',
        '.env*',
        '.git*',
        '.surf',
        'docker-compose.yml',
        'public/fileadmin',
        'README.md'
    ])
    ->setOption('scriptBasePath', \TYPO3\Flow\Utility\Files::concatenatePaths([$deployment->getWorkspacePath($application), $application->getOption('webDirectory')]))
    ->addSymlink($application->getOption('webDirectory') . '/typo3conf/LocalConfiguration.php', '../../../../shared/Configuration/LocalConfiguration.php');

$deployment
    ->addApplication($application)
    ->onInitialize(
        function () use ($deployment, $application) {
            $deployment->getWorkflow()
                ->beforeTask(\TYPO3\Surf\Task\TYPO3\CMS\SetUpExtensionsTask::class, \TYPO3\Surf\Task\TYPO3\CMS\CompareDatabaseTask::class, $application)
                ->beforeStage('transfer', TYPO3\Surf\Task\Php\WebOpcacheResetCreateScriptTask::class, $application)
                ->afterStage('switch', \TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask::class, $application)
                ->removeTask(\TYPO3\Surf\Task\TYPO3\CMS\CreatePackageStatesTask::class, $application)
                ->removeTask(\TYPO3\Surf\Task\TYPO3\CMS\CopyConfigurationTask::class, $application);
        }
    );
