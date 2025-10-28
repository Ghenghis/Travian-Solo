<?php

namespace Database;
class ServerDB
{
    private static $connections = [];

    /**
     * @param $configFileLocation
     *
     * @return \PDO
     * @throws \Exception
     */
    public static function getInstance($configFileLocation)
    {
        $configKey = substr(md5($configFileLocation), 0, 5);
        if (isset(self::$connections[$configKey])) {
            return self::$connections[$configKey];
        }
        if (!is_file($configFileLocation)) {
            throw new \Exception("Configuration file not found!");
        }
        require($configFileLocation);
        if (!isset($connection)) {
            throw new \Exception("Invalid data was in connection file!");
        }

        // Debug: print connection config
        error_log("ServerDB Config - Host: {$connection['database']['hostname']}, Port: {$connection['database']['port']}, DB: {$connection['database']['database']}, File: $configFileLocation");

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false
        ];
        $dsn = 'mysql:charset=utf8mb4;host=' . $connection['database']['hostname'] . ';port=' . $connection['database']['port'] . ';dbname=' . $connection['database']['database'];
        $db = self::$connections[$configKey] = new \PDO($dsn, $connection['database']['username'], $connection['database']['password'], $options);
        $db->exec("set names utf8");
        return $db;
    }
}