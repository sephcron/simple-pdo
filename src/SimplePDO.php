<?php

namespace Sephcron\PDO;

use PDO;
use PDOStatement;

class SimplePDO
{   
    /** @var PDO */
    protected $pdo = null;
    protected $dsn;
    protected $options;
    private $onConnectCallback;
    private $reconnectInterval = 0;
    private $connectionTimestamp = 0;
    
    public function __construct(string $dsn, array $options = [])
    {
        $this->dsn      = $dsn;
        $this->options  = $options;
    }
    
    public function onConnect(callable $callback)
    {
        $this->onConnectCallback = $callback;
    }
    
    public function setReconnectInterval(int $value)
    {
        $this->reconnectInterval = $value;
    }
    
    public function connect()
    {
        if ($this->pdo && $this->reconnectInterval > 0 && ($this->reconnectInterval + $this->connectionTimestamp) < time())
            $this->pdo = null;
            
        if (!$this->pdo)
        {
            $url = parse_url($this->dsn);
        
            $pdo_dsn = $url['scheme'] . ':host=' . ($url['host'] ?? '127.0.0.1') . ';port=' . ($url['port'] ?? 3306);

            if (isset($url['path']))
                $pdo_dsn .= ';dbname=' . substr($url['path'], 1);

            if (isset($url['query']))
                $pdo_dsn .= ';' . str_replace('&', ';', $url['query']);

            $username = $url['user'] ?? null;
            $password = $url['pass'] ?? null;
        
            $this->pdo = new PDO($pdo_dsn, $username, $password, $this->options);

            $this->connectionTimestamp = time();
            
            if ($this->onConnectCallback)
                ($this->onConnectCallback)($this);
        }
        
        return $this->pdo;
    }
    
    public function close(): void
    {
        $this->pdo = null;
    }
    
    public function lastInsertId(?string $name = null)
    {
        return $this->pdo->lastInsertId($name);
    }
    
    public function bindValue(PDOStatement $stmt, string $parameter, $value): void
    {
        if (is_int($value))
            $stmt->bindValue($parameter, $value, PDO::PARAM_INT);

        else 
        if (is_string($value) || is_float($value))
            $stmt->bindValue($parameter, $value, PDO::PARAM_STR);

        else
        if (is_bool($value))
            $stmt->bindValue($parameter, $value, PDO::PARAM_BOOL);

        else
        if (is_null($value))
            $stmt->bindValue($parameter, $value, PDO::PARAM_NULL);
        
        else
        if (is_object($value))
            $stmt->bindValue($parameter, json_encode($value), PDO::PARAM_STR);

        else 
            $stmt->bindValue($parameter, $value);
    }

    public function bindValues(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $param)
            $this->bindValue($stmt, $key, $param);
    }
    
    public function execute(string $sql, array $params = []): int
    {
        if (empty($params))
            return $this->connect()->exec($sql);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function executeMultiple(string $sql, array $params): int
    {
        if (empty($params))
            return 0;
        
        $stmt = $this->connect()->prepare($sql);

        $affectedRows = 0;
        
        foreach ($params as $param_array)
        {
            foreach ($param_array as $key => $param)
                $this->bindValue($stmt, $key, $param);

            $affectedRows += $stmt->execute();
        }
        
        return $affectedRows;
    }
    
    public function insertMultiple(string $sql, array $params): int
    {
        return $this->executeMultiple($sql, $params);
    }
    
    public function updateMultiple(string $sql, array $params): int
    {
        return $this->executeMultiple($sql, $params);
    }
    
    public function executeProcedure(string $name, array $params): array
    {
        $sql = "CALL $name(" . implode(', ', array_keys($params)) . ")";
        
        $stmt = $this->query($sql, $params);
        
        $results = [];
        
        do
        {
            $results[] = $stmt->fetchAll() ?: null;
        }
        while ($stmt->nextRowset());
        
        return $results;
    }
    
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connect()->prepare($sql);

        if (!empty($params))
            $this->bindValues($stmt, $params);

        $stmt->execute();
        return $stmt;
    }
    
    private function flags(string $queryType, $result, array $flags = [])
    {
        if (!$result)
            return $result;
        
        if  (array_key_exists('json', $flags))
            $result = $this->flagJson($queryType, $result, $flags['json']);
        
        return $result;
    }
    
    private function flagJson(string $queryType, $result, $flagValue)
    {        
        $is_obj = ($this->options[\PDO::ATTR_DEFAULT_FETCH_MODE] ?? \PDO::FETCH_ASSOC) === \PDO::FETCH_OBJ;
        
        $decode_row = function ($row, $columns) use ($is_obj) 
        {
            foreach ($columns as $column)
            {
                if ($is_obj && isset($row->{$column}))
                    $row->{$column} = json_decode($row->{$column});
                else if (!$is_obj && isset($row[$column]))
                    $row[$column] = json_decode($row[$column]);
            }
            
            return $row;
        };
        
        switch (strtolower($queryType))
        {
            case 'scalar':
                if ($flagValue === true)
                    $result = json_decode($result);
                
                break;
                
            case 'column':
                if ($flagValue === true)
                    foreach ($result as $row)
                        $result = json_decode($result);
                
                break;
            
            case 'one':
                $result = $decode_row($result, $flagValue);
        
                break;
            
            case 'all':
                foreach ($result as $key => $row)
                    $result[$key] = $decode_row($row, $flagValue);
                
                break;
        }
        
        return $result;
    }
    
    public function queryScalar(string $sql, array $params = [], array $flags = []): mixed
    {
        $stmt = $this->query($sql, $params);
        $value = $stmt->fetchColumn(0);
        
        $result = $value === false ? null : $value;
        return $this->flags('scalar', $result, $flags);
    }
    
    public function queryOne(string $sql, array $params = [], array $flags = []): ?object
    {
        $result = $this->query($sql, $params)->fetch() ?: null;
        return $this->flags('one', $result, $flags);
    }
    
    public function queryAll(string $sql, array $params = [], array $flags = []): array
    {
        $result = $this->query($sql, $params)->fetchAll() ?: [];
        return $this->flags('all', $result, $flags);
    }
    
    public function queryAllIndexed(string $sql, array $params = [], array $flags = []): array
    {
        $result = $this->query($sql, $params)->fetchAll(PDO::FETCH_UNIQUE) ?: [];
        return $this->flags('all', $result, $flags);
    }
    
    public function queryAllGroup(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_GROUP) ?: [];
    }
    
    public function queryColumn(string $sql, array $params = [], array $flags = []): array
    {
        $result = $this->query($sql, $params)->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return $this->flags('column', $result, $flags);
    }
    
    public function queryColumnIndexed(string $sql, array $params = [], array $flags = []): array
    {
        $result = $this->query($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        return $this->flags('column', $result, $flags);
    }
}
