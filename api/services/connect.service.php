<?php

//Class to provide Database Connection for wrapper classes and repetitvely used functions
class ConnectionService {

    // General function to establish connection
    public static function getConnection() {
        static $conn;
        if ($conn === NULL) {
            $conn = new mysqli("localhost", //servername
            "root", //username
            "", //password
            "DCP"
            // dbname
            );
        }
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    }

    // General function which can be used to put wildcards around values usable for SQL-Statements
    public static function toWildcard($value) {
        return "%" . $value . "%";
    }
    
    //Function to provide the Frontend-Address, especially used in e-mail generation
    public static function getFrontendAddress() {
        return "192.168.56.101/#!/";
    }
}
?>
