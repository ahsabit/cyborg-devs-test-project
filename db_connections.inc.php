<?php
    $host = 'localhost';
    $dbname = 'cyborg';
    $username = 'cyborg';
    $password = 'testpass';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }

    session_start();