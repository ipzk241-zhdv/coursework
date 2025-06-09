<?php

namespace classes;

class DB
{
    protected $pdo;
    public function __construct($host, $user, $password, $dbname)
    {

        $this->pdo = new \PDO("mysql:host={$host};dbname={$dbname}", $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    protected function where($where = null, $or = false)
    {
        if (is_array($where)) {
            $where_string = 'WHERE ';
            $where_fields = array_keys($where);
            foreach ($where_fields as $key => $field) {
                if ($key > 0) {
                    if ($or) {
                        $where_string .= ' OR ';
                    } else {
                        $where_string .= ' AND ';
                    }
                }
                $where_string .= "{$field} like :{$field}";
            }
        } elseif (is_string($where)) {
            $where_string = 'WHERE ' . $where;
        } else {
            if (is_string($where)) {
                $where_string = $where;
            } else {
                $where_string = '';
            }
        }
        return $where_string;
    }

    public function select($table, $fields = '*', $where = null, $orderBy = null, $limit = null, $offset = null, $or = false, $search = false)
    {
        if (is_array($fields)) {
            $fields_string = implode(',', $fields);
        } else {
            $fields_string = is_string($fields) ? $fields : '*';
        }

        $params = [];

        if ($search && is_array($where)) {
            [$where_string, $params] = $this->search($where);
        } else {
            $where_string = $this->where($where, $or);
            $params = is_array($where) ? $where : [];
        }

        $order_string = '';
        if (!empty($orderBy)) {
            if (is_array($orderBy)) {
                $order_parts = [];
                foreach ($orderBy as $field => $direction) {
                    $direction = strtoupper($direction);
                    $direction = in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
                    $order_parts[] = "$field $direction";
                }
                $order_string = 'ORDER BY ' . implode(', ', $order_parts);
            } elseif (is_string($orderBy)) {
                $order_string = 'ORDER BY ' . $orderBy;
            }
        }

        $limit_string = '';
        if (is_numeric($limit)) {
            $limit_string .= "LIMIT " . intval($limit);
            if (is_numeric($offset)) {
                $limit_string .= " OFFSET " . intval($offset);
            }
        }

        $sql = "SELECT {$fields_string} FROM {$table} {$where_string} {$order_string} {$limit_string}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function search(array $conditions): array
    {
        $whereParts = [];
        $params = [];
        $paramIndex = 0;

        foreach ($conditions as $field => $value) {
            $words = preg_split('/\s+/', trim($value));
            $fieldParts = [];

            foreach ($words as $word) {
                $paramName = "{$field}_{$paramIndex}";
                $fieldParts[] = "$field LIKE :$paramName";
                $params[$paramName] = $word . '%';
                $paramIndex++;
            }

            // Додати повну фразу (необов’язково, але зазвичай корисно)
            $fullParam = "{$field}_{$paramIndex}";
            $fieldParts[] = "$field LIKE :$fullParam";
            $params[$fullParam] = $value . '%';
            $paramIndex++;

            // Об’єднати усі умови для одного поля через OR
            $group = '(' . implode(' OR ', $fieldParts) . ')';
            $whereParts[] = $group;
        }

        // Між різними полями завжди AND
        $whereString = 'WHERE ' . implode(' OR ', $whereParts);
        return [$whereString, $params];
    }



    public function count($table, $where = null, $or = false, $search = false)
    {
        if (!$search) {
            $params = [];
            $where_string = $this->where($where, $or);

            if (is_array($where)) {
                foreach ($where as $key => $value) {
                    $params[$key] = $value;
                }
            }
        } else {
            $res = $this->search($where);
            $where_string = $res[0];
            $params = $res[1];
        }

        $sql = "SELECT COUNT(*) as count FROM {$table} {$where_string}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? (int)$result['count'] : 0;
    }

    public function insert($table, $row_to_insert)
    {
        $fields_list = implode(',', array_keys($row_to_insert));
        foreach ($row_to_insert as $key => $value) {
            $params_array[] = ":{$key}";
        }
        $params_list = implode(',', $params_array);
        $sql = "INSERT INTO {$table} ({$fields_list}) VALUES ({$params_list})";
        $stmt = $this->pdo->prepare($sql);
        foreach ($row_to_insert as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    public function delete($table, $where = null)
    {
        $where_string = $this->where($where);

        $sql = "DELETE FROM {$table} {$where_string}";
        $stmt = $this->pdo->prepare($sql);
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function update($table, $row_to_update, $where = null)
    {
        $set_string = '';
        foreach ($row_to_update as $key => $value) {
            if ($set_string != '') {
                $set_string .= ', ';
            }
            $set_string .= "{$key} = :{$key}";
        }
        $where_string = $this->where($where);

        $sql = "UPDATE {$table} SET {$set_string} {$where_string}";
        $stmt = $this->pdo->prepare($sql);
        foreach ($row_to_update as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function selectQuery($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function selectOneQuery($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
