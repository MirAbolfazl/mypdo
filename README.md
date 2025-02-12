# MyPDO

MyPDO is a lightweight PHP library that provides a simple and flexible way to interact with MySQL databases using PDO. It offers a fluent interface for building SQL queries and supports various database operations such as SELECT, INSERT, UPDATE, DELETE, and more.

## Features

- Fluent interface for building SQL queries
- Supports SELECT, INSERT, UPDATE, DELETE operations
- Supports WHERE, AND, OR conditions
- Supports JOIN, GROUP BY, ORDER BY, HAVING clauses
- Supports binding parameters to prevent SQL injection
- Debug mode for query inspection

## Installation

To use MyPDO, simply include the `mypdo.php` file in your project:

```php
require_once 'mypdo.php';
```

## Usage

### Creating a MyPDO Instance

First, create a PDO instance and pass it to the MyPDO constructor:

```php
$pdo = new PDO('mysql:host=localhost;dbname=testdb', 'username', 'password');
$mypdo = new MyPDO($pdo);
```

### SELECT Query

```php
$result = $mypdo->select('users', ['id', 'name', 'email'])
                ->where('status', 'active')
                ->orderby('name', 'ASC')
                ->limit(10)
                ->fetchall(PDO::FETCH_OBJ);
```

### INSERT Query

```php
$mypdo->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active'
]);
echo $mypdo->lastInsertId;
```

### UPDATE Query

```php
$mypdo->where('id', 1)->update('users', [
    'email' => 'john.doe@example.com'
]);
```

### DELETE Query

```php
$mypdo->where('id', 1)->delete('users');
```

### JOIN Query

```php
$result = $mypdo->where('orders.status', 'completed')->join('users', 'orders.user_id', 'users.id')->select('orders', ['orders.id', 'users.name'])->fetchall(PDO::FETCH_OBJ);
```

### Debug Mode

Enable debug mode to inspect the generated SQL query:

```php
$query = $mypdo->debug()->where('status', 'active')->select('users', ['id', 'name']);
echo $query;
```

## License

This project is licensed under the MIT License.