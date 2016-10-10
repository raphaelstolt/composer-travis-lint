<?php
namespace Stolt\Composer\Tests\Travis;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Mockery;
use Stolt\Composer\Tests\TestCase;
use Stolt\Composer\Travis;
use Stolt\Composer\Travis\Api\Exceptions\ConnectivityFailure;
use Stolt\Composer\Travis\Api\Exceptions\NonExpectedReponseStructure;
use Stolt\Composer\Travis\Api\Result;

class TravisTest extends TestCase
{
    /**
     * @string
     */
    private $originalWorkingDirectory;

    public function setUp()
    {
        $this->originalWorkingDirectory = getcwd();
        $this->setUpTemporaryDirectory();
        chdir($this->temporaryDirectory);
    }

    public function tearDown()
    {
        $this->removeDirectory($this->temporaryDirectory);
        chdir($this->originalWorkingDirectory);
    }

    /**
     * @test
     */
    public function nonExistentTravisConfigurationFailsLint()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[writeError]'
        );

        $expectedMessage = "Travis CI configuration doesn't exist.";

        $ioMock->shouldReceive('writeError')
            ->once()
            ->with($expectedMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $this->assertTravisConfigurationFileNotExists();
        $this->assertFalse(Travis::lint($eventMock));
    }

    /**
     * @test
     */
    public function nonStaleCacheFileRendersLintSuccessful()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $expectedMessage = 'Travis CI configuration has not '
                . 'changed since the last lint run and is therefore valid.';

        $ioMock->shouldReceive('write')
            ->once()
            ->with($expectedMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $this->createTravisConfigurationFile('ABC');
        $this->createComposerTravisLintCacheFile('ABC');

        $this->assertComposerTravisLintCacheFileExists();
        $this->assertTravisConfigurationFileExists();
        $this->assertTrue(Travis::lint($eventMock));
    }

    /**
     * @test
     */
    public function connectivityFailureFailsLint()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[writeError]'
        );

        $expectedErrorMessage = 'Travis CI lint API request '
                . "failed with HTTP Code '500'.";

        $apiMock = Mockery::mock(
            'Stolt\Composer\Travis\Api[post]'
        );

        $apiMock->shouldReceive('post')
            ->once()
            ->withAnyArgs()
            ->andThrow(new ConnectivityFailure($expectedErrorMessage));

        $ioMock->shouldReceive('writeError')
            ->once()
            ->with($expectedErrorMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $travisConfiguration = <<<CONTENT
language: php
php:
  - '5.4'
  - '5.5'
  - '5.6'
  - '7.0'
  - hhvm
  - nightly
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $this->assertFalse(Travis::lint($eventMock, $apiMock));
    }

    /**
     * @test
     */
    public function nonExpectedReponseStructureFailsLint()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[writeError]'
        );

        $expectedErrorMessage = 'Travis CI lint API responded '
                . 'with a non expected structure.';

        $apiMock = Mockery::mock(
            'Stolt\Composer\Travis\Api[post]'
        );

        $apiMock->shouldReceive('post')
            ->once()
            ->withAnyArgs()
            ->andThrow(new NonExpectedReponseStructure($expectedErrorMessage));

        $ioMock->shouldReceive('writeError')
            ->once()
            ->with($expectedErrorMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $travisConfiguration = <<<CONTENT
language: php
php:
  - '5.4'
  - '5.5'
  - '5.6'
  - '7.0'
  - hhvm
  - nightly
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $this->assertFalse(Travis::lint($eventMock, $apiMock));
    }

    /**
     * @test
     */
    public function successfulLintCreatesCacheFile()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $expectedMessage = <<<CONTENT
Travis CI configuration is valid.
Created '.ctl.cache' file.
CONTENT;

        $apiMock = Mockery::mock(
            'Stolt\Composer\Travis\Api[post]'
        );

        $apiMock->shouldReceive('post')
            ->once()
            ->withAnyArgs()
            ->andReturn(new Result);

        $ioMock->shouldReceive('write')
            ->once()
            ->with($expectedMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $travisConfiguration = <<<CONTENT
language: php
php:
  - '5.4'
  - '5.5'
  - '5.6'
  - '7.0'
  - hhvm
  - nightly
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $this->assertTrue(Travis::lint($eventMock, $apiMock));
        $this->assertComposerTravisLintCacheFileExists();
        $this->assertEquals(
            md5($travisConfiguration) . "\n",
            $this->getComposerTravisLintCacheFileContent()
        );
    }

    /**
     * @test
     */
    public function unsuccessfulLintDeletesCacheFile()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $apiMock = Mockery::mock(
            'Stolt\Composer\Travis\Api[post]'
        );

        $apiMock->shouldReceive('post')
            ->once()
            ->withAnyArgs()
            ->andReturn(new Result(false, 'Travis CI configuration is invalid.'));

        $expectedMessage = <<<CONTENT
Travis CI configuration is invalid.
Deleted '.ctl.cache' file.
CONTENT;

        $ioMock->shouldReceive('writeError')
            ->once()
            ->with($expectedMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $travisConfiguration = <<<CONTENT
language: php
php:
  - '5.4'
  - '5.5'
  - '5.6'
  - '7.0'
  - hhvm
  - nightly
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);
        $this->createComposerTravisLintCacheFile('test');

        $this->assertFalse(Travis::lint($eventMock, $apiMock));
        $this->assertComposerTravisLintCacheFileNotExists();
    }

    /**
     * @test
     * @group integration
     */
    public function successfulLint()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[write]'
        );

        $expectedMessage = <<<CONTENT
Travis CI configuration is valid.
Created '.ctl.cache' file.
CONTENT;

        $ioMock->shouldReceive('write')
            ->once()
            ->with($expectedMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $travisConfiguration = <<<CONTENT
language: php
php:
  - 7.0
  - 7.1
  - nightly
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);

        $this->assertTrue(Travis::lint($eventMock));
        $this->assertComposerTravisLintCacheFileExists();
    }

    /**
     * @test
     * @group integration
     */
    public function failingLint()
    {
        $composerMock = Mockery::mock(
            'Composer\Composer'
        );

        $ioMock = Mockery::mock(
            'Composer\IO\IOInterface[writeError]'
        );

        $expectedMessage = <<<CONTENT
Travis CI configuration is invalid. Warnings:
 - unexpected key "foo", dropping
 - missing key "language", defaulting to "ruby"
Deleted '.ctl.cache' file.
CONTENT;

        $ioMock->shouldReceive('writeError')
            ->once()
            ->with($expectedMessage);

        $eventMock = Mockery::mock(
            'Composer\Script\Event[getIO]',
            ['event-name', $composerMock, $ioMock]
        );

        $eventMock->shouldReceive('getIO')
            ->once()
            ->withNoArgs()
            ->andReturn($ioMock);

        $travisConfiguration = <<<CONTENT
{"foo":"bar"}
CONTENT;

        $this->createTravisConfigurationFile($travisConfiguration);
        $this->createComposerTravisLintCacheFile('test');

        $this->assertFalse(Travis::lint($eventMock));
        $this->assertComposerTravisLintCacheFileNotExists();
    }
}
