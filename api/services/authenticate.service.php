<?php
//Class that provides functions for user authentication related procedures
//This class is used within every of the brokers, whether for user authentication/login or for validating
//incoming requests for data manipulation

require_once 'services/authentication/token.service.php';
require_once 'services/accounts.service.php';
require_once 'services/email/passwordReset/passwordReset.service.php';

class AuthenticationService {

    //This function checks a username and password combination for validity and returns a JSON-object containing
    //data about successful authentication and stores a security token as a cookie
    //to provide a client access to protected operations. In addition, the function takes
    //a third variable containing information about if the token should expire immediately after browser closing or
    //stay within the browser cache for a defined amoount of time representing a remembering-function.
    public static function authenticateUser($username, $password, $rememberMe) {
        //Prepare the result object
        $data = array();
        //if the passwords match, the login was correct and therefore an according token will be provided
        if (AccountsService::validatePassword($username, $password)) {
            //Sets the lifetime of the token according to the given remember-me option
            if ($rememberMe == 0) {
                //If remember-me was not used make the token exprining after browser close
                $expire = 0;
            } else {
                //If remember-me was used make the token expiring after X days
                $expire = time() + 86400 * $rememberMe; //expires in X days (Value in rememberMe);
                
            }
            //Get the user object from the database for later use
            $user = AccountsService::getAccountByUsername($username);
            //Generate the authentication token using the token service while passing the previous user object
            $token = TokenService::generateSessionToken($user);
            $secure = false; //should be set to true later for HTTPS connection
            $httponly = true; //Makes the cookie containing the token non readable through Javascript
            //Stores the generated token with previous options in the browser cache. This service will ask for this token
            //everytime a client tries to perform a protected operation.
            setCookie(TokenService::getSessionTokenName(), $token, $expire, null, null, $secure, $httponly);
            //Preparing a response object about a succesful authentication containing the user id and the user role
            $data["success"] = 0;
            $data["id"] = $user["id"];
            $data["role"] = $user["role"];
            return $data;
        } else {
            //If the user/password comparison was not successful, return an appropriate object containing that information
            $data["success"] = 1;
            return $data;
        }
    }

    //Provides a random CSFR token to prevent site forging for authenticated users that already posess a token
    public static function provideCsrfToken() {
        //Get the token payload of the browser cache
        $tokenPayload = TokenService::getSessionTokenPayload();
        //If the token contains a valid user id, a random hex is provided to the client browser front-end
        //The generated CSRF token will be stored in a session variable of this service
        //This hex will be send back to this service everytime the front-end performs an authorized request
        if (!empty($tokenPayload['id'])) {
            session_start();
            $_SESSION['CsrfToken'] = bin2hex(random_bytes(32));
            $token = $_SESSION['CsrfToken'];
            return $token;
        } else {
            //No valid token was passed
            return 'CSRF token providing failed, No valid token was passed';
        }
    }

    //Represents something like a logout function through deleting the cookie containing the security token
    //Without the token, no protected operatiosn can be carried out
    public static function revertAuthentication() {
        try {
            session_start();
            setcookie(TokenService::getSessionTokenName(), '', time() - 3600);
            $_SESSION['CsrfToken'] = null;
            session_destroy();
            return 0;
        }
        catch(Exception $e) {
            return $e->getMessage();
        }
    }

    //Important function to protect every operation that needs an authenticated and priveledged user. This function will be performed
    //before every request that tries to access a protected operation while verifying various attributes related to the user before allowing access.
    //The function expects three variables:
    //	1. A variable containing information about which user roles are allowed to perform a secured operation. This can be whether...
    //		- ...a string "all" which would make every authenticated user allowed to perform the function
    //		- ...an array containg the user roles as strings (e.g array("1,2") would make administrators and staff eligible for the action).
    //	2. The $callbackFunction variable which contains the actual business logic function to be executed after succesful security validation
    //	3. Any parameter needed by the business logic function stored in $callbackFunction.
    public static function permissionWrapper($allowedRoles, $callbackFunction, $callbackParameter) {
        //Get the token payload stored within the browser cookie
        $tokenPayload = TokenService::getSessionTokenPayload();
        //Checks if the token was decrypted valid through checking if an id exists
        if (!empty($tokenPayload['id'])) {
            //Start a session to compare CSRF tokens
            session_start();
            //Checks if the CSRF token of the server in $_SESSION['CsrfToken'] and the client token sent in $_SERVER['HTTP_X_CSRF_TOKEN'] match
            if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($_SESSION['CsrfToken'] && hash_equals($_SERVER['HTTP_X_CSRF_TOKEN'], $_SESSION['CsrfToken']))) {
                //Get the user that performed the request by using the id stored in the security token
                $user = AccountsService::getAccountById($tokenPayload["id"]);
                //Checks if the user that performed the request has an appropriate role to perform it
                //Before that, it is checked if the user that performed the action still exists
                if (!empty($user)) {
                    //If the $allowedRoles variable contains the string "all", every user is allowed to perform it
                    if ($allowedRoles == "all") {
                        //Performs the actual protected operation after every previous validation was succesful
                        //Therefore, the $callbackFunction gets passed its parameter stored in $callbackParameter. This will cause the operation to be executed.
                        return $callbackFunction($callbackParameter);
                        //If the $allowedRoles variable contains an array, the array stores the allowed roles using int values e.g. array("1,2")
                        //If the user accounts role appears within the array, the user is allowed to perform the operation
                        
                    } elseif (in_array($user["role"], $allowedRoles)) {
                        //Peforms the actual operation as described before.
                        return $callbackFunction($callbackParameter);
                    } else {
                        //User is not allowed to perform this action
                        return 'Validation failed, User is not allowed to perform this action';
                    }
                } else {
                    //User that requested the action does not exist anymore
                    return 'Validation failed, User that requested the action does not exist anymore';
                }
            } else {
                //If the CSRF token was not valid
                return 'CSRF Token was not valid!';
            }
        } else {
            //No valid token was passed
            return 'Validation failed, No valid session token was passed';
        }
    }

    //Function that triggers the process of a password reset via mail
    //Therefore, the function needs the username and the according email
    public static function requestNewPassword($username, $email) {
        //Get the user saved within the database by the passed username
        $user = AccountsService::getAccountByUsername($username);
        //Check that the user exists and that the passed email matches with the email stored within the DB
        if (!empty($user) && $user['email'] == $email) {
            //Trigger the password reset mail generation through passing a textual pw-reset token and the user itself to the appropriate function
            return PasswordResetService::sendPasswordResetMail(TokenService::generatePasswordResetToken($user), $user);
        } else {
            //Username doesn't exist or username and email mismatch occured
            return 1;
        }
    }
    
    //If a user clicks the generated password reset link provided within a mail, this function will be called by the front-end
    //after typing in a new password in an appropriate mask. The function therefore asks for the pw-reset token provided in the mail
    //and the new password to be set.
    public static function resetPassword($token, $password) {
        //Get the payload of the pw-reset token
        $tokenPayload = TokenService::getPasswordResetTokenPayload($token);
        //Get the timestamp of the token
        $timestamp = $tokenPayload["timestamp"];
        //Get the user that requested a new password using the user id stored in the pw-reset token
        $user = AccountsService::getAccountById($tokenPayload["id"]);
        //Get a boolean value if the pw-reset token matches with the one stored in the DB
        //This is to ensure that the token has not been used before
        $isTokenValid = AccountsService::isPasswordResetTokenValid($user, $token);
        //Checks if the token have not been used before
        if ($isTokenValid == true) {
            //Verifies that the token is not older than one hour = 3600 sec
            if (time() - 3600 < $timestamp) {
                //If the Token/User/Time combination is valid the password is reset
                AccountsService::resetPassword($user, $password);
                //The token stored in the DB will be removed to ensure one-time use only
                AccountsService::removePasswordResetToken($user);
                return 0;
            } else {
                //Token valid but too much time has passed
                //User has to request a new token
                return 1;
            };
        } else {
            //Invalid token
            return 2;
        }
    }
}
?>