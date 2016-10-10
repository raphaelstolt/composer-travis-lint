<?php

namespace Stolt\Composer\Tests;

use PHPUnit_Framework_TestCase as PHPUnit;

class TestCase extends PHPUnit
{
    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * @var string
     */
    protected $travisConfigurationFile;

    /**
     * @var string
     */
    protected $composerTravisLintCacheFile;

    /**
     * Set up temporary directory.
     *
     * @return void
     */
    protected function setUpTemporaryDirectory()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            ini_set('sys_temp_dir', '/tmp/ctl');
            $this->temporaryDirectory = '/tmp/ctl';
        } else {
            $this->temporaryDirectory = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'ctl';
        }

        if (!file_exists($this->temporaryDirectory)) {
            mkdir($this->temporaryDirectory);
        }
    }

    /**
     * Remove directory and files in it.
     *
     * @return void
     */
    protected function removeDirectory($directory)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
                continue;
            }
            @unlink($fileinfo->getRealPath());
        }

        @rmdir($directory);
    }

    /**
     * Create Travis CI configuration file.
     *
     * @param  string $content
     * @return void
     */
    protected function createTravisConfigurationFile($content)
    {
        $this->travisConfigurationFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.travis.yml';

        file_put_contents($this->travisConfigurationFile, $content);
    }

    /**
     * Create a .ctl.cache file.
     *
     * @param  string $content
     * @return void
     */
    protected function createComposerTravisLintCacheFile($content)
    {
        $this->composerTravisLintCacheFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.ctl.cache';

        file_put_contents($this->composerTravisLintCacheFile, md5($content) . "\n");
    }

    /**
     * Get the .ctl.cache content.
     *
     * @return string
     */
    protected function getComposerTravisLintCacheFileContent()
    {
        $this->composerTravisLintCacheFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.ctl.cache';

        return file_get_contents($this->composerTravisLintCacheFile);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertComposerTravisLintCacheFileExists($message = '')
    {
        $composerTravisLintCacheFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.ctl.cache';

        $this->assertFileExists($composerTravisLintCacheFile, $message);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertComposerTravisLintCacheFileNotExists($message = '')
    {
        $composerTravisLintCacheFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.ctl.cache';

        $this->assertFileNotExists($composerTravisLintCacheFile, $message);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertTravisConfigurationFileExists($message = '')
    {
        $this->assertFileExists($this->travisConfigurationFile, $message);
    }

    /**
     * Custom assertion.
     *
     * @param string $message
     */
    protected function assertTravisConfigurationFileNotExists($message = '')
    {
        $travisConfigurationFile = $this->temporaryDirectory
            . DIRECTORY_SEPARATOR
            . '.ctl.cache';

        $this->assertFileNotExists($travisConfigurationFile, $message);
    }
}
