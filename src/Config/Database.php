<?php

class Database
{
    private const CONFIG = [
        'host' => 'localhost',
        'dbname' => 'workhive_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];

    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::CONFIG['host'],
                self::CONFIG['dbname'],
                self::CONFIG['charset']
            );

            self::$connection = new PDO(
                $dsn,
                self::CONFIG['username'],
                self::CONFIG['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return self::$connection;
    }
}
