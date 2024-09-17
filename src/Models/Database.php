<?php
class Database 
{
    private $connection;

    public function __construct() 
    {
        $config = require '../config/config.php'; 
        $this->connect($config);
    }

    private function connect($config) 
    {
        $this->connection = new mysqli(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );

        if ($this->connection->connect_error) 
        {
            die("Bağlanti hatasi: " . $this->connection->connect_error);
        }
    }

    public function getConnection() 
    {
        return $this->connection;
    }
}