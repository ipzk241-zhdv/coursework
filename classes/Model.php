<?php

namespace classes;

class Model
{
    protected $fieldsArray;

    protected static $primaryKey = 'id';

    protected static $table = '';
    
    public function __construct()
    {
        $this->fieldsArray = [];
    }
    public function save(): bool
    {
        $value = $this->{static::$primaryKey} == null;

        if ($value) {
            Core::getInstance()->db->insert(static::$table, $this->fieldsArray);
            return true;
        } else {
            Core::getInstance()->db->update(static::$table, $this->fieldsArray,  [static::$primaryKey => $this->{static::$primaryKey}]);
            return true;
        }
    }

    public static function deleteById(int $id)
    {
        Core::getInstance()->db->delete(static::$table, [static::$primaryKey => $id]);
    }

    public static function deleteByCondition($conditionAssocArray)
    {
        Core::getInstance()->db->delete(static::$table, $conditionAssocArray);
    }
    
    public static function findAll($page = null, $limit = null, $orderBy = null)
    {
        if ($page != null && $limit != null) {
            $page = ($page - 1) * $limit;
        } else {
            $page = null;
            $limit = null;
        }
        $arr = Core::getInstance()->db->select(static::$table, '*', null, $orderBy, $limit, $page);
        if (count($arr) > 0) {
            return $arr;
        } else {
            return null;
        }
    }

    public static function findById(int $id, bool $array = false)
    {
        $arr = Core::getInstance()->db->select(static::$table, '*', [static::$primaryKey => $id]);

        if (count($arr) > 0) {
            $row = $arr[0];

            if ($array) {
                return $row;
            }

            $obj = new static();
            foreach ($row as $key => $value) {
                $obj->$key = $value;
            }

            return $obj;
        }

        return null;
    }

    public static function findByCondition($conditionAssocArray, $offset = null, $limit = null, $or = false, $search = false, $orderBy = null)
    {
        $arr = Core::getInstance()->db->select(static::$table, '*', $conditionAssocArray, $orderBy, $limit, $offset, $or, $search);

        return $arr ?: null;
    }

    public function __set($name, $value)
    {
        $this->fieldsArray[$name] = $value;
    }

    public function __get($name)
    {
        return $this->fieldsArray[$name] ?? null;
    }
}
