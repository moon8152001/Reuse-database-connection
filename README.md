CodeIgniter 3 Database Enhancement: Read/Write Splitting & Connection Reuse
This modification enhances the CodeIgniter 3 (CI3) database driver to provide intelligent Read/Write splitting and efficient database connection reuse. It automatically routes queries to the appropriate database server and manages connections to reduce overhead.

🚀 Features
Automatic Read/Write Routing: Analyzes SQL queries to direct SELECT statements to a read-only replica and INSERT/UPDATE/DELETE statements to the master database.
Database Connection Reuse: Caches and reuses established database connections for the same configuration group, preventing unnecessary reconnects.
Sticky Write Mode: After any write operation, a flag is set to force all subsequent queries in the same request to use the master database, ensuring data consistency when reading immediately after a write.
Seamless Integration: Functions automatically with minimal changes to existing application code.

⚙️ Configuration
Edit your application/config/database.php file to define both master and read-only database configurations.
Define a suffix for your read-only group (e.g., ReadOnly).
Configure the default master database under the $db['default'] group.
Configure the read-only replica(s) under a group name that combines the master's group name and the read-only suffix (e.g., defaultReadOnly).

// application/config/database.php
// Define a suffix for read-only database groups

```php
$db_read_only_suffix = 'ReadOnly';
$active_group = 'default';

// Master Database (Read/Write)
$db['default'] = array(
    'dsn'   => '',
    'hostname' => '192.168.61.133', // Master server
    'username' => 'root',
    'password' => '123456',
    'database' => 'test_db',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    // ... other settings remain unchanged
);

// Read-Only Replica Database
$db['default_' . $db_read_only_suffix] = array(
    'dsn'   => '',
    'hostname' => '192.168.61.134', // Read-only replica server
    'username' => 'root',
    'password' => '123456',
    'database' => 'test_db', // Same database name
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    // ... other settings should mirror the master for consistency
);
```
🔧 Core Modifications
This implementation requires changes to three core CI3 files:

1. system/database/DB.php
Modification: The DB() function is updated to store crucial configuration parameters within the returned database object for later access.

```php
// system/database/DB.php (excerpt)
function &DB($params = '')
{
    // ... existing code to load config and determine $active_group ...
    
    // After the driver is instantiated, assign new properties
    $DB->db_read_only_suffix = $db_read_only_suffix ?? 'ReadOnly';
    $DB->default_active_group = $default_active_group ?? 'default';
    $DB->current_db_config = $db ?? null; // Store the entire DB config array
    
    return $DB;
}
```

2. system/core/Loader.php
Modification: The database() method is modified to cache initialized database connection objects in the global config array, enabling connection reuse across the application.

```php
// system/core/Loader.php (excerpt - database method)
public function database($params = '', $return = FALSE)
{
    $CI =& get_instance();
    // ... existing checks ...

    require_once(BASEPATH.'database/DB.php');

    if ($return === TRUE) {
        // Cache and return the connection for reuse
        if (!isset($CI->config->config['cache_db_conn'][$params])) {
            $CI->config->config['cache_db_conn'][$params] = DB($params);
        }
        return $CI->config->config['cache_db_conn'][$params];
    }

    // Load the DB class for the default group
    $CI->db =& DB($params);
    // Cache the default group connection
    $CI->config->config['cache_db_conn'][$CI->db->default_active_group] = $CI->db;
    return $this;
}
```

3. system/database/DB_driver.php
Modification A (New Method): A new method check_current_db_config() contains the core logic for determining the correct database connection based on the operation type (read or write).

```php
// system/database/DB_driver.php (excerpt - new method)
public function check_current_db_config($current_operation = 1)
{
    $CI_CURRENT =& get_instance();
    $default_active_group = $CI_CURRENT->db->default_active_group;
    $db_read_only_suffix = $CI_CURRENT->db->db_read_only_suffix;
    $db_config = $CI_CURRENT->db->current_db_config; // Retrieved from the DB object

    // ... logic to find all config keys for the current database ...
    // ... logic to check 'force_write_db' flag ...

    // Determine the required config key based on operation
    if ($current_operation === 0) { // READ
        // Logic to find a config key containing the read-only suffix
        $need_db_config = $this->find_read_only_config(...);
    } elseif ($current_operation === 1) { // WRITE
        // Logic to find the master config key (without the suffix)
        $need_db_config = $this->find_master_config(...);
        // Set 'force_write_db' flag to stick to master for subsequent requests
        $CI_CURRENT->config->config['cache_db_conn']['force_write_db'] = 1;
    }

    // Get the connection from cache or create it
    if (!isset($CI_CURRENT->config->config['cache_db_conn'][$need_db_config])) {
        $CI_CURRENT->config->config['cache_db_conn'][$need_db_config] = DB($need_db_config);
    }
    // Return the connection resource (e.g., $_mysqli for mysqli driver)
    return $CI_CURRENT->config->config['cache_db_conn'][$need_db_config]->_mysqli;
}
```

Modification B (Query Execution): The simple_query() method is modified to use the new routing logic before executing a query.

```php
// system/database/DB_driver.php (excerpt - modified method)
public function simple_query($sql)
{
    // 1. Determine if the query is a READ or WRITE
    $current_operation = $this->is_write_type($sql) ? 1 : 0;
    // 2. Get the correct connection resource for this operation
    $operation_db_config = $this->check_current_db_config($current_operation);
    // 3. Switch the current connection to the chosen one
    $this->conn_id = $operation_db_config;
    
    // Proceed with the original execution logic
    empty($this->conn_id) && $this->initialize();
    return $this->_execute($sql);
}
```

📋 Usage
Once installed and configured, the functionality is automatic. Use the CodeIgniter database library as you normally would. The driver will handle the routing and connection management behind the scenes.

```php
// This SELECT will be routed to the read-only replica ('defaultReadOnly')
$query = $this->db->get('posts');
// This INSERT will be routed to the master ('default')
$this->db->insert('posts', array('title' => 'New Post'));
// Because a write occurred, this subsequent SELECT will be "stuck" to the master
$query = $this->db->get_where('posts', array('id' => 1));
```

⚠️ Important Notes
Backup: Always back up original core files before modification.
Testing: Thoroughly test this in a development environment before deploying to production. Pay close attention to behavior after write operations.
Transactions: Ensure the logic handles transactions correctly (all queries within a transaction should use the master connection).
Replication Lag: The "sticky" master feature is crucial to prevent reading stale data from a lagging replica immediately after a write.

🤝 Contributing
This is a custom modification. Contributions or suggestions for improvement are welcome.
