Enhanced the CodeIgniter 3.2.0-dev ORM to provide built-in read-write separation for MySQLi. The implementation features:
Query Routing: Automatic routing of write operations (Commands) to the primary node and read operations (Queries) to replica nodes.
Connection Persistence: Maintenance and reuse of established master and replica connections within a request scope for reduced latency and improved efficiency.
Database Layer Integration: Seamless integration into the framework's core database abstraction layer, ensuring backward compatibility.

The following is the configuration description of read-write database
```php
$db_read_only_suffix = 'ReadOnly';
$active_group = 'default';
$db['default'] = array(
	'dsn'	=> '',
	'hostname' => '192.168.61.133',
	'username' => 'root',
	'password' => '123456',
	'database' => 'test_db',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$db['default_' . $db_read_only_suffix] = array(
	'dsn'	=> '',
	'hostname' => '192.168.61.134',
	'username' => 'root',
	'password' => '123456',
	'database' => 'test_db',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
```
The following is a description of the use method
1. Create a master and slave database to run, and create a data table users in each database to insert some data.
2. Access the following url on the browser
```php
http://127.0.0.1/auth/info
```


