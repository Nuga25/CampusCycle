<?php
    $host = "localhost"; // MAMP default host
    $port = 8889; // MAMP default port
    $db = "campuscycle";
    $user = "root"; // MAMP default username
    $pass = "root";  // MAMP default password

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>