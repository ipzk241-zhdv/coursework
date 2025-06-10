<?php

namespace classes;

use classes\Request;
use Utils\Access;
use models\HttpLogs;
use models\Settings;
use models\Layouts;

class Core
{
    public string $action;
    public string $controller;
    private static $instance = null;
    protected Template $mainTemplate;
    protected Template $adminTemplate;
    public $db = null;
    public $session = null;
    public $settings = null;

    public function __construct()
    {
        $this->adminTemplate = new Template('layout/admin/index.php');
        $config = Config::getInstance();
        $this->db = new DB($config->host, $config->user, $config->password, $config->dbname);
        $this->session = new Session();
    }

    protected function InitSettings()
    {
        $set = Settings::findById(1, true);
        if ($set) {
            $this->settings = $set;
            $layout = Layouts::findById($this->settings['current_layout_id'], true);
            if ($layout) {
                $this->settings['current_layout'] = $layout['path'];
            } else {
                Core::Log("====== ПОМИЛКА ЗАВАНТАЖЕННЯ НАЗВИ ТЕМПЛЕЙТУ ======");
                $this->settings['current_layout'] = 'light';
            }
            $this->settings['exclude_cache'] = json_decode($this->settings['exclude_cache'], true);
        } else {
            Core::log("====== ПОМИЛКА ЗАВАНТАЖЕННЯ НАЛАШТУВАНЬ ======");
            $this->settings = [
                'current_layout' => 'light',
                'to_cache' => true,
                'cache_lifetime' => 3600,
                'exclude_cache' => [],
            ];
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function Init(): void
    {
        $this->InitSettings();
        $this->mainTemplate = new Template('layout/' . $this->settings['current_layout'] . '/index.php');
        session_start();
    }

    public function run(): void
    {
        $this->parseRoute();
        $controllerClass = 'controllers\\' . ucfirst($this->controller) . 'Controller';
        $actionMethod = 'action' . ucfirst($this->action);

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $actionMethod)) {
            $this->respondError(404);
            return;
        }

        // Перевірка прав доступу через атрибут #[Access]
        try {
            $reflection = new \ReflectionMethod($controllerClass, $actionMethod);
            $attributes = $reflection->getAttributes(Access::class);

            if (!empty($attributes)) {
                $access = $attributes[0]->newInstance();
                $userRole = $this->session->get('user')['role'] ?? null;

                if (!Access::hasAccess($userRole, $access->roles)) {
                    if (in_array('admin', $access->roles)) {
                        $this->log(Access::hasAccess($userRole, $access->roles));
                        $this->respondError(404);
                    } else {
                        $this->respondError(403);
                    }
                    return;
                }
            }
        } catch (\ReflectionException $e) {
            $this->respondError(500);
            return;
        }


        $controllerObject = new $controllerClass;
        $args = $this->resolveArguments($controllerObject, $actionMethod);
        //$data = $controllerObject->$actionMethod(...$args);
        $data = $this->handleCache(function () use ($controllerObject, $actionMethod, $args) {
            return $controllerObject->$actionMethod(...$args);
        });

        if (Request::isAjax() || Request::method() === "POST") {
            echo is_array($data) || is_object($data) ? json_encode($data) : $data;
            exit;
        }

        if (is_string($data)) {
            $data = ['content' => $data];
        }

        $this->getTemplate()->addParams($data);
        $this->logHttpError(200, '');
        $this->done();
    }


    protected function handleCache(callable $callback)
    {
        $nocache = Request::get('nocache');

        if ($nocache) {
            $cacheEnabled = false;
        } else {
            $cacheEnabled = $this->settings['to_cache'];
        }

        $cacheLifetime = $this->settings['cache_lifetime']; // in seconds
        $cacheKey = md5($_SERVER['REQUEST_URI']);
        $cachePath = __DIR__ . '/../cache/pages/' . $cacheKey . '.html';

        Core::log(['controller' => $this->controller, 'action' => $this->action, 'settings' => $this->settings['exclude_cache']]);

        if (in_array($this->action, $this->settings['exclude_cache'] ?? [], true) || $this->controller === 'Forum') {
            $cacheEnabled = false;
        }

        if (Request::method() === "POST") {
            $cacheEnabled = false;
        }

        if ($cacheEnabled && file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheLifetime) {
            return file_get_contents($cachePath);
        }

        $data = $callback();

        if ($cacheEnabled && !$this->isAdminController() && Request::method() !== "POST") {
            if (is_array($data) && isset($data['content'])) {
                $html = $data['content'];
            } else {
                $html = is_string($data) ? $data : '';
            }

            $dir = dirname($cachePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($cachePath, $html);
        }

        return $data;
    }

    protected function parseRoute(): void
    {
        $route = Request::get('route', '');
        $parts = explode('/', trim($route, '/'));

        if ($parts[0] === 'forum') {
            $this->controller = 'Forum';
            $count = count($parts);

            if ($count === 1) {
                // /forum
                $this->action = 'index';
            } elseif ($count === 2) {
                // /forum/uploadimage
                if ($parts[1] === 'commentImage') {
                    $this->action = 'commentImage';
                    // /forum/createThread
                } else if ($parts[1] === 'createthread') {
                    $this->action = 'createThread';
                    // /forum/like
                } else if ($parts[1] === 'like') {
                    $this->action = 'like';
                } else {
                    // /forum/{subcategorySlug}
                    $this->action = 'subcategory';
                    $_GET['subcategory'] = $parts[1];
                }
            } elseif ($count === 3) {
                if ($parts[2] === 'comment') {
                    // /forum/{threadId}/comment
                    $this->action = 'comment';
                    $_GET['thread_id'] = (int)$parts[1];
                } elseif (is_numeric($parts[2])) {
                    // /forum/{subcategorySlug}/{threadId}
                    $this->action = 'thread';
                    $_GET['subcategory'] = $parts[1];
                    $_GET['id'] = (int)$parts[2];
                } else {
                    // /forum/{entity}/{id}
                    $this->action = 'handleBaseAction';
                    $_GET['entity'] = $parts[1];
                    $_GET['id'] = $parts[2];
                }
            } elseif ($count === 4) {
                if ($parts[2] === 'comment') {
                    // /forum/{threadId}/comment/{commentId}
                    $this->action = 'comment';
                    $_GET['thread_id'] = (int)$parts[1];
                    $_GET['comment_id'] = (int)$parts[3];
                } else {
                    $this->respondError(404);
                    exit;
                }
            } else {
                $this->respondError(404);
                exit;
            }
        } else {
            // Default route fallback
            if (count($parts) === 1 && $parts[0] !== '') {
                $this->controller = 'Site';
                $this->action = $parts[0];
            } else {
                $this->controller = $parts[0] ?: 'Site';
                $this->action = $parts[1] ?? 'view';
            }
        }
    }


    protected function resolveArguments(object $controller, string $method): array
    {
        $ref = new \ReflectionMethod($controller, $method);
        $params = $ref->getParameters();
        $data = Request::all();
        unset($data['route']);

        $args = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $args[] = $data[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
        }

        return $args;
    }

    protected function getTemplate(): Template
    {
        $user = $this->session->get('user');
        if ($this->isAdminController() && (!$user || $user['role'] !== 'admin')) {
            return $this->mainTemplate;
        }
        return $this->isAdminController() ? $this->adminTemplate : $this->mainTemplate;
    }

    protected function isAdminController(): bool
    {
        return strtolower($this->controller) === 'admin';
    }

    public function error(int $code): void
    {
        http_response_code($code);
        $template = $this->getTemplate();
        $contentTemplate = new Template("views/errors/{$code}.php");

        $template->addParam('content', $contentTemplate->render());
        echo $template->render();
        exit;
    }

    public function respondError(int $code, string $message = '', array $extra = []): void
    {
        http_response_code($code);

        $this->logHttpError($code, $message, $extra);

        if (Request::isAjax()) {
            echo json_encode([
                'status' => 'error',
                'code' => $code,
                'message' => $message,
                'extra' => $extra
            ]);
            exit;
        }

        $this->error($code);
    }

    public function done(): void
    {
        $this->getTemplate()->display();
    }

    public static function log($var)
    {
        error_log(print_r($var, true));
    }

    protected function logHttpError(int $code, string $message, array $extra = []): void
    {
        $user = $this->session->get('user');
        $data = [
            'status_code' => $code,
            'message' => $message,
            'extra' => json_encode($extra),
            'user_id' => $user['id'] ?? null,
            'method' => Request::method(),
            'is_ajax' => Request::isAjax(),
            'path' => Request::get('route', ''),
            'ip' => Request::getUserIp()
        ];

        HttpLogs::apiCreate($data);
    }
}
