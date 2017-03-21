<?php

namespace cy\client;

use cy\client\response\ResponseInterface;
use cy\client\response\AsyncResponse;
use cy\client\exception\BadResponseException;
use GuzzleHttp\Promise;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;
use GuzzleHttp\Psr7\Response as GuzzleHttpResponse;
use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;

/**
 * Client
 *
 *
 * Simple
 *
 * try {
 *     $r = $client->get('member', 'employee/search');
 *     if ($r->hasError()) {
 *         throw new Exception($r->getMessage());
 *     }
 * } catch {\Exception $e} {
 *     var_dump($e->getMessage());
 * }
 *
 * var_dump($r->getData());
 *
 *
 * Batch
 *
 * $req1 = $client->createRequest();
 * $req1->setRemote('member')->setPath('aaaa')->setMethod('GET');
 *
 * $req2 = $client->createRequest();
 * $req2->setRemote('member')->setPath('bbbb')->setMethod('POST');
 *
 * $reps = $client->addRequest('r1', $req1)->addRequest('r2', $req2)->call();
 *
 * var_dump($reps['r1']->getData());
 * var_dump($reps['r2']->getData());
 *
 *
 * Post File
 *
 * $multipart = [['name' => 'file', 'contents' => fopen('xxx.jpeg', 'r')]];
 * $res = $client->postMultipart('napi', 'base.upload.upload.image', $multipart);
 *
 */
class Client
{
    const EVENT_BEFORE_SEND = 'beforeSend';

    const EVETN_AFTER_RECV = 'afterRecv';

    /**
     * @var integer
     */
    public $timeout = 5;

    /**
     * @var array
     */
    public $headers = [
        'token' => 'xx',
    ];

    /**
     * @var array
     */
    public $remotes = [];

    /**
     * @var string
     */
    public $defaultResponseClass = 'cy\client\response\Response';

    /**
     * @var array
     */
    public $responseClassMap = [
        'napi' => 'cy\client\response\NapiResponse',
    ];

    /**
     * @var Request[]
     */
    protected $requests = [];

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }

        $this->on(self::EVENT_BEFORE_SEND, [$this, 'beginProfile']);
        $this->on(self::EVETN_AFTER_RECV, [$this, 'endProfile']);
        $this->on(self::EVETN_AFTER_RECV, [$this, 'trace']);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        if (strncmp($name, 'on ', 3) === 0) {
            $this->on(trim(substr($name, 3)), $value);
        } else {
            throw new \Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * @param string $remote
     * @return string
     * @throws \Exception
     */
    public function remote($remote)
    {
        if (empty($this->remotes)) {
            throw new \Exception(sprintf('Remote %s not found.', $remote));
        }

        return rtrim($this->remotes[$remote][mt_rand(0, count($this->remotes[$remote]) - 1)], '/');
    }

    /**
     * @param string $remote
     * @param string $path
     * @param array|string $query
     * @param int $timeout
     * @param boolean $async
     * @return ResponseInterface
     */
    public function get($remote, $path, $query = [], $timeout = null, $async = false)
    {
        $request = $this->createRequest();
        $request->setRemote($remote)->setPath($path)->setQuery($query)->setTimeout($timeout)->setMethod(Request::GET);

        return $this->send($request, $async);
    }

    /**
     * @param string $remote
     * @param string $path
     * @param string|array $formParams
     * @param int $timeout
     * @param boolean $async
     * @return ResponseInterface
     */
    public function post($remote, $path, $formParams = [], $timeout = null, $async = false)
    {
        $request = new Request();
        $request->setRemote($remote)->setPath($path)->setFormParams($formParams)->setTimeout($timeout)
            ->setMethod(Request::POST);

        return $this->send($request, $async);
    }

    /**
     * @param string $remote
     * @param string $path
     * @param array $multipart
     * @param int $timeout
     * @param boolean $async
     * @return ResponseInterface
     */
    public function postMultipart($remote, $path, $multipart, $timeout = null, $async = false)
    {
        $request = new Request();
        $request->setRemote($remote)->setPath($path)->setMultipart($multipart)->setTimeout($timeout)
            ->setMethod(Request::POST);

        return $this->send($request, $async);
    }

    /**
     * @param string $remote
     * @param string $path
     * @param string|array $json
     * @param int $timeout
     * @param boolean $async
     * @return ResponseInterface
     */
    public function postJson($remote, $path, $json, $timeout = null, $async = false)
    {
        $request = new Request();
        $request->setRemote($remote)->setPath($path)->setJson($json)->setTimeout($timeout)
            ->setMethod(Request::POST);

        return $this->send($request, $async);
    }

    /**
     * @param $key
     * @param Request $request
     * @return self
     */
    public function addRequest($key, Request $request)
    {
        $this->requests[$key] = $request;

        return $this;
    }

    /**
     * @param Request[] $requests
     * @return self
     */
    public function addRequests(array $requests)
    {
        $this->requests = array_merge($this->requests, $requests);

        return $this;
    }

    /**
     * @return ResponseInterface[]
     */
    public function call()
    {
        $results = [];
        $promises = [];

        $guzzleHttpClient = new GuzzleHttpClient();

        foreach ($this->requests as $key => $request) {
            $this->preTreatedRequest($request);
            list($guzzleHttpRequest, $options) = $this->createGuzzleRequest($request);
            $promises[$key] = $guzzleHttpClient->sendAsync($guzzleHttpRequest, $options);
        }

        $responses = Promise\unwrap($promises);

        foreach ($responses as $key => $response) {
            $results[$key] = $this->createResponse($this->requests[$key], $response);
        }

        $this->requests = [];

        return $results;
    }

    /**
     * @param Request $request
     * @param boolean $async
     * @return ResponseInterface|null
     */
    public function send(Request $request, $async = false)
    {
        $this->preTreatedRequest($request);

        $this->trigger(self::EVENT_BEFORE_SEND, $request);

        /**
         * @var $guzzleHttpRequest GuzzleHttpRequest
         * @var $options array
         */
        list($guzzleHttpRequest, $options) = $this->createGuzzleRequest($request);

        $guzzleHttpClient = new GuzzleHttpClient();

        if (!$async) {
            try {
                $guzzleHttpResponse = $guzzleHttpClient->send($guzzleHttpRequest, $options);
            } catch (GuzzleBadResponseException $e) {
                $guzzleHttpResponse = $e->getResponse();
            }
            $response = $this->createResponse($request, $guzzleHttpResponse);
        } else {
            $handler = new CurlMultiHandler();
            $guzzleHttpClient = new GuzzleHttpClient([
                'handler' => HandlerStack::create($handler),
            ]);
            $guzzleHttpClient->sendAsync($guzzleHttpRequest, $options);
            $handler->tick();
            $response = new AsyncResponse($request);
            register_shutdown_function(function () use ($handler) {
                $handler->execute();
            });
        }

        $this->trigger(self::EVETN_AFTER_RECV, $response);

        return $response;
    }

    /**
     * @param string $name
     * @param callback $handler
     * @return $this
     */
    public function on($name, $handler)
    {
        $this->events[$name][] = $handler;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $sender
     */
    protected function trigger($name, $sender)
    {
        if (!empty($this->events[$name])) {
            foreach ($this->events[$name] as $handler) {
                call_user_func($handler, $sender);
            }
        }
    }

    /**
     * @param Request $request
     */
    protected function beginProfile(Request $request)
    {
        if (class_exists('Yii') && defined('YII_DEBUG') && YII_DEBUG) {
            $profileToken = $request->getUri();
            call_user_func(['Yii', 'beginProfile'], $profileToken, 'ClientProfile');
        }
    }

    /**
     * @param ResponseInterface $response
     */
    protected function endProfile(ResponseInterface $response)
    {
        if (class_exists('Yii') && defined('YII_DEBUG') && YII_DEBUG) {
            $profileToken = $response->getRequest()->getUri();
            call_user_func(['Yii', 'endProfile'], $profileToken, 'ClientProfile');
        }
    }

    /**
     * @param ResponseInterface $response
     */
    protected function trace(ResponseInterface $response)
    {
        if (class_exists('Yii') && defined('YII_DEBUG') && YII_DEBUG) {
            $request = $response->getRequest();

            $traces[] = sprintf('Request api: %s, Request method: %s', $request->getUri(), $request->getMethod());

            if ($request->getMethod() == Request::GET) {
                $params = $request->getQuery();
            } else {
                $params = $request->getFormParams() ?: ($request->getJson() ?: $request->getMultipart());
            }

            $traces[] = 'Request params:';
            $traces[] = var_export($params, true);
            $traces[] = 'Return:';
            $traces[] = var_export(json_decode($response->getBody(), true), true);

            $message = implode(PHP_EOL, $traces);

            call_user_func(['Yii', 'trace'], $message);
        }
    }

    /**
     * @return Request
     */
    public function createRequest()
    {
        return new Request();
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function createGuzzleRequest(Request $request)
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        if ($method == Request::GET && $query = $request->getQuery()) {
            $uri .= '?' . http_build_query($query);
        }

        $options = $request->getOptions();
        $options['headers'] = array_merge($this->headers, $request->getHeaders());

        if ($ip = Util::getIp()) {
            $options['headers']['x-forwarded-for'] = $ip;
        }

        if ($options['timeout'] === null) {
            $options['timeout'] = $this->timeout;
        }

        $guzzleHttpRequest = new GuzzleHttpRequest($method, $uri);

        return [$guzzleHttpRequest, $options];
    }

    /**
     * @param Request $request
     */
    protected function preTreatedRequest(Request $request)
    {
        $uri = $this->remote($request->getRemote()) . '/' . $request->getPath();
        $request->setUri($uri);
    }

    /**
     * @param Request $request
     * @param GuzzleHttpResponse $guzzleHttpResponse
     * @return ResponseInterface
     * @throws \Exception
     * @throws BadResponseException
     */
    protected function createResponse(Request $request, GuzzleHttpResponse $guzzleHttpResponse)
    {
        $responseClass = $this->getResponseClass($request->getRemote());
        $response = new $responseClass($guzzleHttpResponse, $request);

        if (!$response instanceof ResponseInterface) {
            throw new \Exception('Response must be ResponseInterface');
        }

        /*if (!$response->getBody() && ($response->getCode() === null || $response->getMessage() === null)) {
            throw new BadResponseException($response);
        }*/

        return $response;
    }

    /**
     * @param $remote
     * @return mixed|string
     */
    protected function getResponseClass($remote)
    {
        if (isset($this->responseClassMap[$remote])) {
            return $this->responseClassMap[$remote];
        }

        return $this->defaultResponseClass;
    }
}
