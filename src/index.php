<?php
$host = 'db';        // service name from docker-compose
$db   = 'myapp';
$user = 'user';
$pass = 'userpass';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "✅ Connected to MySQL successfully!<br>";
    
    $stmt = $pdo->query("SELECT VERSION()");
    echo "MySQL version: " . $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}