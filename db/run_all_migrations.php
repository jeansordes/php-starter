<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/utilities.php';
require_once __DIR__ . '/../app/sql-utilities.php';
loadDotEnv();

echo "Current directory: " . getcwd() . "\n";

function executeMigrations() {
    $db = new DB();
    
    // Get all migration files
    $migration_files = glob(__DIR__ . '/migrate/*.sql');
    sort($migration_files); // Ensure files are executed in order
    
    foreach ($migration_files as $file) {
        echo "Executing migration: " . basename($file) . "\n";
        $sql = file_get_contents($file);
        
        // Split the SQL file into individual statements
        $statements = array_filter(
            array_map('trim', 
                // Split on semicolons, but not within quoted strings
                preg_split("/;(?=(?:[^']*'[^']*')*[^']*$)/", $sql)
            ),
            'strlen'
        );
        
        try {
            foreach ($statements as $statement) {
                if (trim($statement) === '') continue;
                
                // Skip PRAGMA statements in non-SQLite databases
                if (strtolower($_ENV['db_type']) !== 'sqlite3' && 
                    stripos($statement, 'PRAGMA') === 0) {
                    continue;
                }
                
                try {
                    $db->query($statement);
                    echo "Statement executed successfully\n";
                } catch (Exception $e) {
                    echo "Error executing statement: " . $e->getMessage() . "\n";
                    echo "Statement was: " . $statement . "\n";
                    // Continue with next statement
                }
            }
            echo "Migration completed: " . basename($file) . "\n";
        } catch (Exception $e) {
            echo "Error in migration " . basename($file) . ": " . $e->getMessage() . "\n";
            // Continue with next migration
        }
    }
    
    echo "All migrations completed!\n";
}

// Execute migrations
executeMigrations(); 