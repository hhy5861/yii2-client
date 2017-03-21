<?php

namespace cy\client\response;

class NapiResponse extends Response
{
    /**
     * @var string
     */
    protected $codeName = 'err';

    /**
     * @var string
     */
    protected $messageName = 'msg';

    /**
     * @var string
     */
    protected $dataName = 'output';
}
