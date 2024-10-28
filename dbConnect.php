<?php
// localhost
// $host = 'localhost';
// $db_name = 'easy_service';
// $username = 'root';
// $password = '';

// demo live server for api testing
$host = 'sql12.freesqldatabase.com';
$db_name = 'sql12741117';
$username = 'sql12741117';
$password = 'P6x3TsRbDr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>
