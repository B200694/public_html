<?php
$hostname = '127.0.0.1';
$database = 's2150996_IWD2_database';
$username = 's2150996';
$password = 'Magpie7$War15Tah';

try {
    // Create a PDO instance (database connection)
    $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    
    // Set PDO to throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}
?>
