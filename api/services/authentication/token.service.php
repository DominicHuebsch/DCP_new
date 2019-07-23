<?php
//This class is a wrapper class for the firebaseJWT framework to generate tokens
//It has the ability to create and validate two types of tokens due to
//predefined private keys in this class
//
//The first token is the security token that is provided to authenticated users.
//This token will be stored in the browser cookies and checked eveytime, the client tries
//to access a secured operation of the service for validity.
//The token payload contains the specific user id as integer.
//
//The second token is the token used to reset a user password if the password was forgotten
//The token will be provided to the user via mail and consumed by the front-end client while
//offering an input mask to set a new password. When the password reset action is triggered, the
//password reset token provided in the according mail will be checked for validity.
//The token payload contains the specific user id and a timestamp when the token was created.
//
//Include the firebaseJWT framework
require_once 'firebaseJWT/jwt.php';

//Define the namespace for the JWT framework
use \Firebase\JWT\JWT;

class TokenService {
    //Returns the name of the security token to be stored in the browser cookies
    public static function getSessionTokenName() {
        return 'dcpSessionToken';
    }
    //Private key for the security token, DO NOT SHARE!!!
    public static function getSessionKey() {
        return 'FS2kKc3sjHQKtz$~anHR^dE{%"x9]*=/';
    }
    //Private key for the password reset token, DO NOT SHARE!!!
    public static function getPasswordResetKey() {
        return '2\>e%LPwNp[7%z-zM>PK_K)?Y3ggK%5n';
    }
    //Generates a security token for a authenticated user
    //while using a user object as a parameter
    public static function generateSessionToken($user) {
        //Create the payload array
        $payloadArray = array();
        //Add the user id to the payload
        $payloadArray['id'] = $user["id"];
        //Creates and returns the token
        return JWT::encode($payloadArray, TokenService::getSessionKey());
    }
    //Generates a password reset token for a authenticated user
    //while using a user object as a parameter
    public static function generatePasswordResetToken($user) {
        //Same working principle as generateSessionToken($user)
        $payloadArray = array();
        $payloadArray['id'] = $user["id"];
        $payloadArray['timestamp'] = time();
        return JWT::encode($payloadArray, TokenService::getPasswordResetKey());
    }
    //Function to read and return the payload of a security token
    //No parameter required as the token will be read from the clients browser
    public static function getSessionTokenPayload() {
        //If a cookie with the name defined in getSessionTokenName() exists,
        //the token content will be read out
        if (!empty($_COOKIE[TokenService::getSessionTokenName() ])) {
            $token = $_COOKIE[TokenService::getSessionTokenName() ];
            try {
                //Try to decode the security token with the according private key
                $payload = JWT::decode($token, TokenService::getSessionKey(), array('HS256'));
                //Create an array that stores the payload elements
                $returnArray = array('id' => $payload->id);
                //Return the array
                return $returnArray;
            }
            catch(Exception $e) {
                //If the token could not be decrypted with the private key
                //E.g. if the private key changed
                echo 'The provided token is invalid!';
                exit();
            }
        } else {
            //If no appropriate cookie containing the security token was found
            echo 'No token was provided in this action';
            exit();
        }
    }
    //Function to read and return the payload of a security token
    //The password reset token needs to be provided to the function as that token
    //comes from the email and is not stored within the browser cookies
    public static function getPasswordResetTokenPayload($token) {
        //Same working principle as getSessionTokenPayload() with the only difference
        //that the password reset token is not read from the cookies but provided as
        //a function parameter
        if (!empty($token)) {
            try {
                $payload = JWT::decode($token, TokenService::getPasswordResetKey(), array('HS256'));
                $returnArray = array('id' => $payload->id, 'timestamp' => $payload->timestamp);
                return $returnArray;
            }
            catch(Exception $e) {
                echo 'The provided token is invalid!';
                exit();
            }
        } else {
            echo 'No token was provided in this action';
            exit();
        }
    }
}
?>