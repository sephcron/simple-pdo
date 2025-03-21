<?php

namespace Sephcron\PDO;

use PDOStatement;

class SimplePDOMySql extends SimplePDO
{
    public function bindValue(PDOStatement $stmt, string $parameter, $value): void
    {
        if (is_bool($value))
            $value = $value ? 1 : 0;
        
        if (is_string($value) && trim($value) === '')
            $value = null;
        
        if ($value instanceof \DateTime)
            $value = $value->format('Y-m-d H:i:s');

        parent::bindValue($stmt, $parameter, $value);
    }
    
    public function insertMultiple(string $sql, array $params): void
    {
        if (!$params)
            return 0;
        
        $new_params = array();
        $new_values = array();

        $index = 0;

        foreach ($params as $param_array)
        {
            $values = array();
            
            foreach ($param_array as $key => $param)
            {
                $new_key = $key . '_' . $index;
                $new_params[$new_key] = $param;
                $values[] = $new_key;
            }

            $new_values[] = '(' . implode(',', $values) . ')';

            $index++;
        }

        $new_sql = str_replace(':VALUES', implode(',', $new_values), $sql);
        
        return $this->execute($new_sql, $new_params);
    }
}