<?php

namespace classes;
use classes\Core;

class Controller
{
    protected Template $template;
    protected $errorMessages = [];
    protected $successMessages = [];

    public function __construct()
    {
        $action = Core::getInstance()->action;
        $controller = Core::getInstance()->controller;
        $this->template = new Template("views/{$controller}/{$action}.php");
    }

    public function view(string $title, ?array $params = null, ?string $templatePath = null): array
    {
        if (!empty($templatePath)) {
            $this->template->setTemplatePath($templatePath);
        }

        if (!empty($params)) {
            $this->template->addParams($params);
        }

        return [
            'title' => $title,
            'content' => $this->template->render(),
        ];
    }

    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
