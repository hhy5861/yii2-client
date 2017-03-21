<?php

namespace cy\client;

class Request
{
    const GET = 'GET';

    const POST = 'POST';

    /**
     * @var string
     */
    protected $remote;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $method = self::GET;

    /**
     * @var array
     */
    protected $options = [
        'timeout' => 5,
        'headers' => [],
    ];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $remote
     * @return $this
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = trim($path, '/');

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string|array $query
     * @return $this
     */
    public function setQuery($query)
    {
        if (is_string($query)) {
            parse_str($query, $query);
        } elseif (!is_array($query)) {
            throw new \InvalidArgumentException('query params must be array or string.');
        }

        $this->options['query'] = $query;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getQuery()
    {
        return Util::getValue($this->options, 'query');
    }

    /**
     * @param string|array $formParams
     * @return $this
     */
    public function setFormParams($formParams)
    {
        if (is_string($formParams)) {
            parse_str($formParams, $formParams);
        } elseif (!is_array($formParams)) {
            throw new \InvalidArgumentException('form params must be array or string.');
        }

        $this->options['form_params'] = $formParams;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getFormParams()
    {
        return Util::getValue($this->options, 'form_params');
    }

    /**
     * @param string|array $json
     * @return $this
     */
    public function setJson($json)
    {
        if (is_string($json)) {
            $json = json_decode($json, true);

            if (!is_array($json)) {
                throw new \InvalidArgumentException('json params error.');
            }
        } elseif (!is_array($json)) {
            throw new \InvalidArgumentException('json params must be array or string.');
        }

        $this->options['json'] = $json;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getJson()
    {
        return Util::getValue($this->options, 'json');
    }

    /**
     * @param array $multipart
     * @return $this
     */
    public function setMultipart(array $multipart)
    {
        $this->options['multipart'] = $multipart;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getMultipart()
    {
        return Util::getValue($this->options, 'multipart');
    }

    /**
     * @param integer $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->options['timeout'] = $timeout;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTimeout()
    {
        return Util::getValue($this->options, 'timeout');
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->options['headers'][$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return Util::getValue($this->options, 'headers', []);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setData($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getData($name)
    {
        return Util::getValue($this->data, $name);
    }
}
