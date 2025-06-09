<?php

namespace classes;

class Template
{
    protected string $templatePath;
    protected array $params;

    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
        $this->params = [];
    }

    public function addParam(string $key, $value)
    {
        $this->params[$key] = $value;
    }
    public function addParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    public function setTemplatePath(string $templatePath)
    {
        $this->templatePath = $templatePath;
    }
    public function getParams()
    {
        return $this->params;
    }
    public function render()
    {
        if (file_exists($this->templatePath)) {
            extract($this->params);
            ob_start();
            include $this->templatePath;
            return ob_get_clean();
        } else {
            ob_start();
            http_response_code(404);
            include 'views/errors/404.php';
            return ob_get_clean();
        }
    }
    public function display()
    {
        echo $this->render();
    }
}
