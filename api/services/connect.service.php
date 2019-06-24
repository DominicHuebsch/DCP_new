<?php

class ConnectionService{
// General function to establish connection
public static function getConnection(){
	static $conn;
	if ($conn === NULL){
		$conn = new mysqli(
            "localhost", //servername
		    "root", //username
		    "", //password
            "DCP"// dbname
        );
	}

	// Check connection

	if ($conn->connect_error){
		die("Connection failed: " . $conn->connect_error);
	}

	return $conn;
}

// General function which can be used to parse values to string
public static function toString($value){
	return "'" . $value . "'";
}

public static function toWildcard($value){
	return "%" . $value . "%";
}

public static function getFrontendAddress(){
	return "192.168.56.101/";
}
}



?>
