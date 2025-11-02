<?php
class databaseModel {
    public static function pdo(): PDO {
        $host = $_ENV['DB_HOST'] ?? 'db';
        $db   = $_ENV['DB_DATABASE'] ?? 'newsapp';
        $user = $_ENV['DB_USERNAME'] ?? 'app';
        $pass = $_ENV['DB_PASSWORD'] ?? 'secret';
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}