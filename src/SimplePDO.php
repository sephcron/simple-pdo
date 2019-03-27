<?php

namespace Sephcron\PDO;

use PDO;
use PDOStatement;

class SimplePDO
{   
    /** @var PDO */
    protected $pdo;
    protected $dsn;
    protected $options;
    protected $connected = false;
    
    public function __construct(string $dsn, array $options = null)
    {
        $this->dsn      = $dsn;
        $this->options  = $options ?: [];
    }
    
    public function connect()
    {
        if (!$this->connected)
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
            
            $this->connected = true;
        }
        
        return $this->pdo;
    }
    
    public function lastInsertId(string $name = null)
    {
        return $this->pdo->lastInsertId($name);
    }
    
    public function bindValue(PDOStatement $stmt, string $parameter, $value)
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
            $stmt->bindValue($parameter, $value);
    }

    public function bindValues(PDOStatement $stmt, array $params)
    {
        foreach ($params as $key => $param)
            $this->bindValue($stmt, $key, $param);
    }

    public function execute(string $sql, array $params = null): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function executeMultiple(string $sql, array $params)
    {
        if (!$params)
            return;
        
        $stmt = $this->connect()->prepare($sql);

        foreach ($params as $param_array)
        {
            foreach ($param_array as $key => $param)
                $this->bindValue($stmt, $key, $param);

            $stmt->execute();
        }
    }
    
    public function insertMultiple(string $sql, array $params)
    {
        $this->executeMultiple($sql, $params);
    }
    
    public function updateMultiple(string $sql, array $params)
    {
        $this->executeMultiple($sql, $params);
    }
    
    public function executeProcedure(string $name, array $params)
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
    
    public function query(string $sql, array $params = null): PDOStatement
    {
        $stmt = $this->connect()->prepare($sql);

        if ($params !== null)
            $this->bindValues($stmt, $params);

        $stmt->execute();
        return $stmt;
    }
    
    public function queryScalar(string $sql, array $params = null)
    {
        $stmt = $this->query($sql, $params);
        $value = $stmt->fetchColumn(0);
        
        return  $value === false ? null : $value;
    }
    
    public function queryOne(string $sql, array $params = null)
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }
    
    public function queryAll(string $sql, array $params = null)
    {
        return $this->query($sql, $params)->fetchAll() ?: null;
    }
    
    public function queryAllIndexed(string $sql, array $params = null)
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_UNIQUE) ?: null;
    }
    
    public function queryAllGroup(string $sql, array $params = null)
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_GROUP) ?: null;
    }
    
    public function queryColumn(string $sql, array $params = null)
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_COLUMN) ?: null;
    }
    
    public function queryColumnIndexed(string $sql, array $params = null)
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR) ?: null;
    }
    
    public function utilIndexByColumn(array &$result, string $column)
    {
        $new = [];
        
        foreach ($result as $row)
            $new[$row->{$column}] = $row;
            
        $result = $new;
    }
}