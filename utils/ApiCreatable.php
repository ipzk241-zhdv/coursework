<?php

namespace Utils;

trait ApiCreatable
{
    public static function apiCreate(array $data)
    {
        $data = cleanFormData($data);
        $obj = new static();
        foreach ($data as $key => $value) {
            $obj->$key = $value;
        }
        return $obj->save() ? $obj : null;
    }
}

function cleanFormData(array $data, array $reservedKeys = ['add', 'put', 'delete', 'post', 'get'])
{
    foreach ($reservedKeys as $key) {
        unset($data[$key]);
    }
    return $data;
}