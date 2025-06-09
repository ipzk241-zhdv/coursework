<?php

namespace classes;

class Request
{
    private static ?array $jsonBody = null;

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function getUserIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }


    public static function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($contentType, 'application/json') !== false;
    }

    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null)
    {
        if (self::isJson()) {
            self::parseJsonBody();
            return self::$jsonBody[$key] ?? $default;
        }

        return $_POST[$key] ?? $default;
    }

    public static function all(): array
    {
        if (self::method() === 'POST') {
            if (self::isJson()) {
                self::parseJsonBody();
                return self::$jsonBody;
            }
            return $_POST;
        }

        return $_GET;
    }

    public static function only(array $keys): array
    {
        $data = self::all();
        return array_intersect_key($data, array_flip($keys));
    }

    public static function has(string $key): bool
    {
        if (self::method() === 'POST' && self::isJson()) {
            self::parseJsonBody();
            return isset(self::$jsonBody[$key]);
        }

        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    private static function parseJsonBody(): void
    {
        if (self::$jsonBody === null) {
            $raw = file_get_contents('php://input');
            self::$jsonBody = json_decode($raw, true);

            if (!is_array(self::$jsonBody)) {
                self::$jsonBody = [];
            }
        }
    }

    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
