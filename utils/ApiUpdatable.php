<?php

namespace Utils;

trait ApiUpdatable
{
    public static function apiUpdate(array $data)
    {
        $data = cleanFormData($data);
        $obj = static::findById($data['id']);
        if (!$obj) return null;

        foreach ($data as $key => $value) {
            $obj->$key = $value;
        }

        return $obj->save() ? $obj : null;
    }
}
