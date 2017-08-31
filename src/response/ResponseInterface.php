<?php

namespace mike\client\response;

use mike\client\Request;

interface ResponseInterface
{
    /**
     * @return Request
     */
    public function getRequest();

    /**
     * @return integer
     */
    public function getStatusCode();

    /**
     * @return string
     */
    public function getBody();

    /**
     * @return integer
     */
    public function getCode();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return boolean
     */
    public function hasError();
}
