<?php

namespace Stolt\Composer;

use Composer\Script\Event;
use Stolt\Composer\Travis\Api;
use Stolt\Composer\Travis\Api\Exceptions\ConnectivityFailure;
use Stolt\Composer\Travis\Api\Exceptions\NonExpectedReponseStructure;

class Travis
{
    const CTL_CACHE = '.ctl.cache';
    const TRAVIS_CONFIGURATION = '.travis.yml';

    /**
     * Check if composer travis lint cache is stale.
     *
     * @return boolean
     */
    protected static function hasStaleCacheFile()
    {
        $composerTravisLintCache = getcwd()
            . DIRECTORY_SEPARATOR
            . self::CTL_CACHE;

        $travisConfiguration = getcwd()
            . DIRECTORY_SEPARATOR
            . self::TRAVIS_CONFIGURATION;

        if (file_exists($composerTravisLintCache) && file_exists($travisConfiguration)) {
            $travisConfigurationContent = trim(file_get_contents($travisConfiguration));
            $composerTravisLintCacheContent = trim(file_get_contents($composerTravisLintCache));

            if (md5($travisConfigurationContent) === $composerTravisLintCacheContent) {
                return false;
            }
        }

        return true;
    }

    /**
     * The Composer script to lint a Travis CI configuration file.
     *
     * @param  Event    $event
     * @param  Api|null $api
     * @return boolean
     */
    public static function lint(Event $event, Api $api = null)
    {
        $composerTravisLintCache = getcwd()
            . DIRECTORY_SEPARATOR
            . self::CTL_CACHE;
        $io = $event->getIO();

        if (!file_exists(self::TRAVIS_CONFIGURATION)) {
            $io->writeError("Travis CI configuration doesn't exist.");

            return false;
        }

        if (self::hasStaleCacheFile() === false) {
            $message = 'Travis CI configuration has not '
                . 'changed since the last lint run and is therefore valid.';

            $io->write($message);

            return true;
        }

        $travisConfigContent = trim(
            file_get_contents(realpath(self::TRAVIS_CONFIGURATION))
        );

        if ($api === null) {
            $api = new Api();
        }

        try {
            $result = $api->post($travisConfigContent);

            if ($result->isSuccessful()) {
                $message = 'Travis CI configuration is valid.';

                $bytesWritten = file_put_contents(
                    $composerTravisLintCache,
                    md5($travisConfigContent) . "\n"
                );

                if ($bytesWritten > 0) {
                    $message .= PHP_EOL . "Created '" . self::CTL_CACHE . "' file.";
                }

                $io->write($message);

                return true;
            }

            $errorMessage = $result->getFailure();

            if (file_exists($composerTravisLintCache)) {
                unlink($composerTravisLintCache);
                $errorMessage .= PHP_EOL . "Deleted '" . self::CTL_CACHE . "' file.";
            }

            $io->writeError($errorMessage);

            return false;
        } catch (ConnectivityFailure $f) {
            $io->writeError($f->getMessage());

            return false;
        } catch (NonExpectedReponseStructure $n) {
            $io->writeError($n->getMessage());

            return false;
        }
    }
}
