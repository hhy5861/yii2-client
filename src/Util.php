<?php

namespace cy\client;

class Util
{
    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue($array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * @return string|null
     */
    public static function getIp()
    {
        return self::getValue($_SERVER, 'HTTP_X_FORWARDED_FOR', self::getValue($_SERVER, 'REMOTE_ADDR'));
    }
}
