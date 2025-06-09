<?php

namespace classes;

class Config
{
    protected static $instance = null;
    protected $params;


    private function __construct()
    {
        /** @var array $Config  */
        $this->params = [];
        $directory = 'config';
        $config_files = scandir($directory);

        foreach ($config_files as $file) {
            if (substr($file, -4) === '.php') {
                $path  = $directory . "/" . $file;
                include($path);
            }
        }
        foreach ($Config as $config) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __get($key)
    {
        return $this->params[$key] ?? null;
    }
    public function __set($key, $value)
    {
        $this->params[$key] = $value;
    }
}
