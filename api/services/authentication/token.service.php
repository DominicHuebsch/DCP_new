<?php

require_once 'firebaseJWT/jwt.php';
//require_once 'services/accounts.service.php';

use \Firebase\JWT\JWT;

class TokenService {

    public static function getSessionTokenName(){
        return 'dcpSessionToken';
    }
    
    public static function getSessionKey(){
        return 'FS2kKc3sjHQKtz$~anHR^dE{%"x9]*=/';
    }
    
    public static function getPasswordResetKey(){
        return '2\>e%LPwNp[7%z-zM>PK_K)?Y3ggK%5n';
    }
    
    public static function generateSessionToken($user){
    
        $payloadArray = array();
        $payloadArray['id'] = $user["id"];
        return JWT::encode($payloadArray, TokenService::getSessionKey());
    
    }
    
    public static function generatePasswordResetToken($user){
    
        $payloadArray = array();
        $payloadArray['id'] = $user["id"];
        $payloadArray['timestamp'] = time();
        return JWT::encode($payloadArray, TokenService::getPasswordResetKey());
    
    }
    
    public static function getSessionTokenPayload(){
        if(!empty($_COOKIE[TokenService::getSessionTokenName()])){
            $token=$_COOKIE[TokenService::getSessionTokenName()];
            try {
                $payload = JWT::decode($token, TokenService::getSessionKey(), array('HS256'));
                $returnArray = array('id' => $payload->id);
                return $returnArray;
            }
            catch(Exception $e) {
                echo 'The provided token is invalid!';
                exit();
                //echo $e->getMessage();
            }

        }else {
            echo 'No token was provided in this action';
            exit();
        }
    }
    
    public static function getPasswordResetTokenPayload($token){
    
        if (!empty($token)) {
    
            try {
                $payload = JWT::decode($token, TokenService::getPasswordResetKey(), array('HS256'));
                $returnArray = array(
                    'id' => $payload->id,
                    'timestamp' => $payload->timestamp
                );
                return $returnArray;
            }
            catch(Exception $e) {
                echo 'The provided token is invalid!';
                exit();
            }
        } 
        else {
            echo 'No token was provided in this action';
            exit();
        }
    }

}



/**
* get access token from header
* */
/* function getBearerToken() {
	$headers = getAuthorizationHeader();
	// HEADER: Get the access token from the header
	if (!empty($headers)) {
		if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
			return $matches[1];
		}
	}
	return null;
	} */

/** 
 * Get header Authorization
 * */
/*  function getAuthorizationHeader(){
	$headers = null;
	if (isset($_SERVER['Authorization'])) {
		$headers = trim($_SERVER["Authorization"]);
	}
	else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
		$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	} elseif (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		//print_r($requestHeaders);
		if (isset($requestHeaders['Authorization'])) {
			$headers = trim($requestHeaders['Authorization']);
		}
	}
	return $headers;
} */


?>