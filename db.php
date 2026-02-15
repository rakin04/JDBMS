<?php
$host = 'localhost';
$dbname = "jdbms_db";  // Make sure this matches your PHPMyAdmin Database name
$user = 'root';
$pass = '';       // Default XAMPP password is empty

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>