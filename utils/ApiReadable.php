<?php

namespace Utils;

trait ApiReadable
{
    public static function apiFindAll(int $page = 1, int $limit = 20, string $orderBy = null)
    {
        return static::findAll($page, $limit, $orderBy);
    }

    public static function apiFindById(int $id)
    {
        return static::findById($id, true);
    }
}