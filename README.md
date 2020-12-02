# simple-pdo

Este projeto tem como objetivo simplificar ao máximo a utilização das classses PDO.

## Conexão

Deve ser fornecida uma string de conexão em formato URI além de outras opções específicas do PDO, se necessário.

`mysql://{username}:{password}@{host}:{port}/{dbname}?{params}`

```php
$dsn = 'mysql://username:pa$$w0rd@127.0.0.1:3306/testdb?charset=utf8mb4';

$pdo = new SimplePDO($dsn, [
    \PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES 'utf8mb4', SESSION sql_mode = 'TRADITIONAL'",
    \PDO::ATTR_DEFAULT_FETCH_MODE    => \PDO::FETCH_OBJ,
    \PDO::ATTR_EMULATE_PREPARES      => false,
    \PDO::ATTR_ERRMODE               => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_PERSISTENT            => false,
]);
```

A conexão será efetuada apenas quando houver necessidade de comunicação com o banco de dados.

## Métodos

Todos os métodos retornam o resultado completo da consulta, ao invés de retornar linha por linha. Ao trabalhar com resultados que possuem grandes quantidades de dados, o uso de memória será maior.

`queryAll`: retorna um conjunto de linha como resultado, ou array vazio.

```php
$sql = "SELECT * FROM users WHERE age > :age";

$result = $pdo->queryAll($sql, [
    ':age' => 20,
]);
```

`queryOne`: retorna apenas uma como resultado, ou null.

```php
$sql = "SELECT * FROM users WHERE id > :id";

$result = $pdo->queryOne($sql, [
    ':id' => 123,
]);
```

`queryScalar`: retorna apenas um valor como resultado, ou null.

```php
$sql = "SELECT name FROM users WHERE id > :id";

$result = $pdo->queryScalar($sql, [
    ':id' => 123,
]);
```

`queryColumn`: retorna apenas uma coluna (podendo conter várias linhas) como resultado, ou array vazio.

```php
$sql = "SELECT name FROM users WHERE age > :age";

$result = $pdo->queryColumn($sql, [
    ':age' => 20,
]);
```
