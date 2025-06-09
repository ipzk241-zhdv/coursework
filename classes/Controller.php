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

    protected function requireLogin()
    {
        if (empty(Core::getInstance()->session->get('user'))) {
            http_response_code(403);
            exit("Доступ заборонено");
        }
    }

    protected function requireRole(string $role)
    {
        $user = Core::getInstance()->session->get('user');
        if (empty($user) || $user['role'] !== $role) {
            http_response_code(403);
            exit("Недостатньо прав доступу");
        }
    }

    protected function requireRoles(array $roles)
    {
        $user = Core::getInstance()->session->get('user');
        if (empty($user) || !in_array($user['role'], $roles)) {
            http_response_code(403);
            exit("Недостатньо прав доступу");
        }
    }
}
