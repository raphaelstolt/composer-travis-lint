<?php

namespace Stolt\Composer\Travis;

use Stolt\Composer\Travis\Api\Exceptions\ConnectivityFailure;
use Stolt\Composer\Travis\Api\Exceptions\NonExpectedReponseStructure;
use Stolt\Composer\Travis\Api\Result;

class Api
{
    const API_ENDPOINT = 'https://api.travis-ci.org/lint';
    const CURL_TIMEOUT_IN_SECONDS = 2;
    const CURL_USERAGENT = 'composer-travis-lint';

    /**
     * @var resource
     */
    private $curlChannel;

    public function __construct()
    {
        $this->curlChannel = curl_init();
    }

    /**
     * Posts the Travis CI configuration to the Travis CI Api.
     *
     * @param  string $travisConfiguration      The content of .travis.yml.
     * @throws ConnectivityFailure
     * @throws NonExpectedReponseStructure
     * @return Stolt\Composer\Travis\Api\Result The post/lint result.
     */
    public function post($travisConfiguration)
    {
        $headers = [
            'Accept: application/vnd.travis-ci.2+json',
            'Content-Type: text/yaml',
            'Content-Length: ' . strlen($travisConfiguration),
        ];

        curl_setopt($this->curlChannel, CURLOPT_USERAGENT, self::CURL_USERAGENT);
        curl_setopt($this->curlChannel, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curlChannel, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlChannel, CURLOPT_CONNECTTIMEOUT, self::CURL_TIMEOUT_IN_SECONDS);
        curl_setopt($this->curlChannel, CURLOPT_TIMEOUT, self::CURL_TIMEOUT_IN_SECONDS);
        curl_setopt($this->curlChannel, CURLOPT_MAXREDIRS, 3);
        curl_setopt($this->curlChannel, CURLOPT_POSTFIELDS, $travisConfiguration);
        curl_setopt($this->curlChannel, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curlChannel, CURLOPT_URL, self::API_ENDPOINT);
        curl_setopt($this->curlChannel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlChannel, CURLOPT_SSL_VERIFYPEER, false);

        $lintResult = curl_exec($this->curlChannel);
        $responseInfo = curl_getinfo($this->curlChannel);

        curl_close($this->curlChannel);

        $apiRequestStatusCode = intval($responseInfo['http_code']);

        if ($apiRequestStatusCode >= 400) {
            $message = 'Travis CI lint API request '
                . " failed with HTTP Code '$apiRequestStatusCode'.";

            throw new ConnectivityFailure($message);
        }

        $lintResult = json_decode($lintResult, true);

        if (!isset($lintResult['lint']) || !isset($lintResult['lint']['warnings'])) {
            $message = 'Travis CI lint API responded '
                . 'with a non expected structure.';

            throw new NonExpectedReponseStructure($message);
        }

        if (count($lintResult['lint']['warnings']) === 0) {
            return new Result;
        }

        if (count($lintResult['lint']['warnings']) > 0) {
            $lintWarningsMessages = PHP_EOL;

            foreach ($lintResult['lint']['warnings'] as $warning) {
                $lintWarningsMessages .= ' - ' . $warning['message'] . PHP_EOL;
            }

            $failure = 'Travis CI configuration is invalid. '
                . 'Warnings:' . rtrim($lintWarningsMessages);

            return new Result(false, $failure);
        }
    }
}
