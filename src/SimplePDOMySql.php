<?php

namespace Sephcron\PDO;

use PDOStatement;

class SimplePDOMySql extends SimplePDO
{
    public function bindValue(PDOStatement $stmt, string $parameter, $value)
    {
        if (is_bool($value))
            $value = $value ? 1 : 0;
        
        if (is_string($value) && $value === '')
            $value = null;

        parent::bindValue($stmt, $parameter, $value);
    }
    
    public function insertMultiple(string $sql, array $params)
    {
        if (!$params)
            return 0;
        
        $new_params = array();
        $new_values = array();

        $index = 0;

        foreach ($params as $param_array)
        {
            $values = array();

            //if (is_object($param_array))
            //    $param_array = (array)$param_array;
            
            foreach ($param_array as $key => $param)
            {
                if ($param instanceof DateTime)
                    $param = $param->format('Y-m-d H:i:s');
                
                if (substr($key, 0, 1) === '_')
                    $values[] = $param;
                
                else
                {
                    $new_key = $key . '_' . $index;
                    $new_params[$new_key] = $param;
                    $values[] = $new_key;
                }
            }

            $new_values[] = '(' . implode(',', $values) . ')';

            $index++;
        }

        $new_sql = str_replace(':VALUES', implode(',', $new_values), $sql);
        
        return $this->connect()->prepare($new_sql)->execute($new_params);
    }
}