<?php

// In a git-based install, the autoloader can be found in the root
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} // In a composer-based install, it's a few levels up.
elseif (file_exists(dirname(__DIR__, 3) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
}

if (!class_exists(modmore\Gitify\Gitify::class)) {
    echo "Error: Unable to find autoloader. Please follow the installation instructions at https://github.com/modmore/Gitify/wiki/1.-Installation\n";
    exit(1);
}

/**
 * Ensure the timezone is set; otherwise you'll get a shit ton (that's a technical term) of errors.
 */
if (version_compare(phpversion(), '5.3.0') >= 0) {
    $tz = @ini_get('date.timezone');
    if (empty($tz)) {
        date_default_timezone_set(@date_default_timezone_get());
    }
}

/**
 * Specify the working directory, if it hasn't been set yet.
 */
if (!defined('GITIFY_WORKING_DIR')) {
    $cwd = getcwd() . DIRECTORY_SEPARATOR;
    $cwd = str_replace('\\', '/', $cwd);
    define('GITIFY_WORKING_DIR', $cwd);
}

/**
 * Specify the user home directory, for save cache folder of gitify
 */
if (!defined('GITIFY_CACHE_DIR')) {
    $cacheDir = '.gitify';

    $home = rtrim(getenv('HOME'), DIRECTORY_SEPARATOR);
    if (!$home && isset($_SERVER['HOME'])) {
        $home = rtrim($_SERVER['HOME'], DIRECTORY_SEPARATOR);
    }
    if (!$home && isset($_SERVER['HOMEDRIVE']) && isset($_SERVER['HOMEPATH'])) {
        // compatibility to Windows
        $home = rtrim($_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'], DIRECTORY_SEPARATOR);
    }
    if (!$home || !is_writable($home)) {
        // fallback to working directory, if home directory can not be determined
        $home = rtrim(GITIFY_WORKING_DIR, DIRECTORY_SEPARATOR);
        // in working directory .gitify file contains the main configuration,
        // cache folder cannot be with the same name (file systems restricts)
        $cacheDir = '.gitify-cache';
    }

    define('GITIFY_CACHE_DIR', implode(DIRECTORY_SEPARATOR, [$home, $cacheDir, '']));
}

/**
 * Load all the commands and create the Gitify instance
 */

use modmore\Gitify\Command\BackupCommand;
use modmore\Gitify\Command\BuildCommand;
use modmore\Gitify\Command\ClearCacheCommand;
use modmore\Gitify\Command\DownloadModxCommand;
use modmore\Gitify\Command\ExtractCommand;
use modmore\Gitify\Command\InitCommand;
use modmore\Gitify\Command\InstallModxCommand;
use modmore\Gitify\Command\InstallPackageCommand;
use modmore\Gitify\Command\RestoreCommand;
use modmore\Gitify\Command\UpgradeModxCommand;
use modmore\Gitify\Gitify;

$version = trim(@file_get_contents(__DIR__ . '/VERSION'));

$application = new Gitify('Gitify', $version);
$application->add(new InitCommand);
$application->add(new BuildCommand);
$application->add(new DownloadModxCommand);
$application->add(new ExtractCommand);
$application->add(new InstallModxCommand);
$application->add(new UpgradeModxCommand);
$application->add(new InstallPackageCommand);
$application->add(new BackupCommand);
$application->add(new RestoreCommand);
$application->add(new ClearCacheCommand);
/**
 * We return it so the CLI controller in /bin/gitify can run it, or for other integrations to
 * work with the gitify api directly.
 */
return $application;
