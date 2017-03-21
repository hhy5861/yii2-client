<?php

namespace cy\client\exception;

use cy\client\response\ResponseInterface;

class BadResponseException extends \Exception
{
    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $message = sprintf(
            'Request %s return error: ' . PHP_EOL . PHP_EOL . '%s',
            $response->getRequest()->getUri(),
            $response->getBody()
        );

        parent::__construct($message);
    }
}
