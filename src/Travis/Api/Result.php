<?php

namespace Stolt\Composer\Travis\Api;

class Result
{
    /**
     * @var boolean
     */
    protected $isSuccessful;

    /**
     * @var string
     */
    protected $failure;

    /**
     * @param boolean $isSuccessful Is a successful lint?
     * @param string  $failure      Lint failure.
     */
    public function __construct($isSuccessful = true, $failure = null)
    {
        $this->isSuccessful = $isSuccessful;
        $this->failure = $failure;
    }

    /**
     * Guard for a successful lint.
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->isSuccessful === true;
    }

    /**
     * Accessor for the lint failure.
     *
     * @return string
     */
    public function getFailure()
    {
        return $this->failure;
    }
}
