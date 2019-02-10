<?php
namespace SilverStripe\Upgrader\Util;

use Composer\Semver\Semver;
use Humbug\SelfUpdate\Updater;
use InvalidArgumentException;
use SilverStripe\Upgrader\CodeCollection\CodeChangeSet;
use SilverStripe\Upgrader\CodeCollection\DiskCollection;
use SilverStripe\Upgrader\CodeCollection\ItemInterface;
use SilverStripe\Upgrader\Composer\ComposerInterface;
use SilverStripe\Upgrader\Composer\SilverstripePackageInfo;
use Symfony\Component\Console\Exception\LogicException;

/**
 * Class to help check if the current PHAR executable is outdated.
 */
class UpdateChecker
{

    /**
     * @var Updater $updater
     */
    private static $updater;

    /**
     * Build an updater object.
     * @param string $version
     * @return Updater
     */
    public static function getUpdater(string $version): Updater
    {
        if (self::$updater) {
            return self::$updater;
        }

        $checkSignature = false;
        self::$updater = new Updater(\Phar::running(false), $checkSignature, Updater::STRATEGY_GITHUB);
        /** @var GithubStrategy $strategy */
        $strategy = self::$updater->getStrategy();
        $strategy->setPharName('upgrade-code.phar');
        $strategy->setPackageName('silverstripe/upgrader');
        $strategy->setCurrentLocalVersion($version);
        return self::$updater;
    }

    /**
     * Get the latest upgrader version available if the message has not been displayed or false otherwise.
     *
     * This saves the value in memory, so you don't always get the message.
     *
     * @return false|string
     */
    public static function getShowNewVersion($currentVersion)
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.upgrade-code.phar.latestversion';
        // Check if we have check the date in the last 2 days.
        if (file_exists($path) && filemtime($path) > (time() - 48 * 60 * 60)) {
            return false;
        }

        $updater = self::getUpdater($currentVersion);
        if ($updater->hasUpdate()) {
            $version = self::getUpdater($currentVersion)->getNewVersion();
        } else {
            $version = $currentVersion;
        }

        file_put_contents($path, $version);
        return $version;
    }
}
