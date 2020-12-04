# simple-pdo

Este projeto tem como objetivo simplificar a utilização das classes PDO.

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

### Consultas

Todos os métodos retornam o resultado completo da consulta, ao invés de retornar linha por linha. Ao trabalhar com resultados que possuem grandes quantidades de dados, o uso de memória será maior.

`queryAll`: retorna um conjunto de linha como resultado, ou array vazio.

```php
$sql = "SELECT * FROM users WHERE age > :age";

$result = $pdo->queryAll($sql, [
    ':age' => 20,
]);
```

`queryAllIndexed`: semelhante ao `queryAll`, mas é possível informar uma coluna para ser utilizada como índice. Como esta coluna será utilizada como índice do array, esta deverá ter um valor único por linha.

```php
$sql = "SELECT id, name, age FROM users WHERE age > :age";

$result = $pdo->queryAllIndexed($sql, [
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
`queryColumnIndexed`: semelhante ao `queryColumn`, mas é possível informar uma coluna para ser utilizada como índice, o que resulta em um conjunto de dados no formato chave/valor. Como esta coluna será utilizada como índice do array, esta deverá ter um valor único por linha.

```php
$sql = "SELECT id, name FROM users WHERE age > :age";

$result = $pdo->queryColumnIndexed($sql, [
    ':age' => 20,
]);
```
### Alterações
`execute`: executa um comando e retorna o número de linhas afetadas.

```php
$sql    = <<<SQL
        UPDATE users SET
            name = :name,
            age = :age
        WHERE id = :id
        SQL;

$result = $pdo->execute($sql, [
    ':id'       => 123,
    ':name'     => 'Joe',
    ':age'      => 25,
]);
```

`executeMultiple`: executa o mesmo comando múltiplas vezes, uma para cada conjunto de parâmetros (linha).

```php
$sql    = <<<SQL
        UPDATE users SET
            name = :name,
            age = :age
        WHERE id = :id
        SQL;

$result = $pdo->executeMultuple($sql, [
    [
        ':id'       => 123,
        ':name'     => 'Joe',
        ':age'      => 25,
    ],
    [
        ':id'       => 456,
        ':name'     => 'John',
        ':age'      => 30,
    ],
]);
```

`insertMultiple`: Alias para `executeMultiple`. Na classe `SimplePDOMySql`, este método é sobrescrito para tirar proveito do *bulk insert*.

`updateMultiple`: Alias para `executeMultiple`.

`lastInsertId`: Alias para `lastInsertId` da classe `PDO`.

## JSON

Alguns métodos possuem o argumento `flags`. Atualmente aceita apenas a chave `json`, onde é possível informar os campos que possuem valores json, fazendo a decodificação destes valores no processo.

```php
$sql = "SELECT name, age, address, contact FROM users WHERE id > :id";

$result = $pdo->queryOne($sql, [
    ':id' => 123,
], 
[
    'json' => ['address', 'contact']
]);
```
