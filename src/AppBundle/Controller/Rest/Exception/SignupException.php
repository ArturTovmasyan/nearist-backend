<?php

namespace AppBundle\Controller\Rest\Exception;

use Throwable;

class SignupException extends \Exception
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * SignupException constructor.
     * @param string $message
     * @param int $code
     * @param array $data
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, array $data = [], Throwable $previous = null)
    {
        $this->data = $data;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}