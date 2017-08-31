<?php

namespace mike\client\response;

use mike\client\Util;
use mike\client\Request;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;

class Response implements ResponseInterface
{
    /**
     * @var integer
     */
    protected $successCode = 0;

    /**
     * @var string
     */
    protected $codeName = 'code';

    /**
     * @var string
     */
    protected $messageName = 'message';

    /**
     * @var string
     */
    protected $dataName = 'data';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $unserializeBody = [];

    /**
     * @var string body
     */
    protected $body;

    /**
     * @var GuzzleHttpResponse
     */
    protected $guzzleHttpResponse;

    /**
     * @param GuzzleHttpResponse $guzzleHttpResponse
     * @param Request $request
     */
    public function __construct(GuzzleHttpResponse $guzzleHttpResponse, Request $request)
    {
        $this->request = $request;
        $this->guzzleHttpResponse = $guzzleHttpResponse;
        $this->body = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$this->guzzleHttpResponse->getBody()));
        $unserializeBody = json_decode($this->getBody(), true);

        if ($unserializeBody) {
            $this->unserializeBody = $unserializeBody;
        }
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->guzzleHttpResponse->getStatusCode();
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return integer
     */
    public function getCode()
    {
        return Util::getValue($this->unserializeBody, $this->codeName);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return Util::getValue($this->unserializeBody, $this->messageName);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return Util::getValue($this->unserializeBody, $this->dataName);
    }

    /**
     * 接口调用返回的错误码是否表示有错误
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->getCode() != $this->successCode;
    }
}
