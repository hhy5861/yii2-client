<?php

namespace mike\client\response;

use mike\client\Request;

class AsyncResponse implements ResponseInterface
{
    /**
     * @var int
     */
    public $statusCode = 200;

    /**
     * @var integer
     */
    protected $code = 0;

    /**
     * @var string
     */
    protected $message = 'Success';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return json_encode([
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'data' => $this->getData()
        ], 320);
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function hasError()
    {
        return false;
    }
}
