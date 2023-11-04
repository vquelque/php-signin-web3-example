<?php 
$servertype = "mysql"; 
$servername = "localhost";
$serverport = 3306; // mysql: 3306
$username = "siweUser";
$password = getenv('DB_PASS');
$dbname = "siwe_test";
$tablename = "siwe";

try {
        if ($servertype == "mysql") {
                $dsn = "mysql:host=$servername;port=$serverport;dbname=$dbname;";
        } else {
                die ('DB config error');
        }
        $conn = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
catch (PDOException $e) {
        die($e->getMessage());
}
