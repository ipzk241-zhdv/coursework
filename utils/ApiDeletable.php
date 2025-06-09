<?php

namespace Utils;

trait ApiDeletable
{
    public static function apiDelete(int $id): bool
    {
        static::deleteById($id);
        return true;
    }
}
