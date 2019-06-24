<?php

require_once 'services/authentication/token.service.php';
require_once 'services/accounts.service.php';
require_once 'services/email/passwordReset/passwordReset.service.php';

class AuthenticationService{

    public static function authenticateUser($username, $password, $rememberMe){
        $passwordFromClient = $password;
        $passwordFromServer = AccountsService::getAccountPassword($username);
    
        $data=array();

        if ($passwordFromServer == $passwordFromClient)
            {
                if ($rememberMe==0){
                    $expire=0;
                }else{
                    $expire=time()+86400*$rememberMe; //expires in X days (Value in rememberMe);
                }

                $user=AccountsService::getAccountByUsername($username);
                $token=TokenService::generateSessionToken($user);

                $secure=false; //should be set to true later for HTTPS connection
                $httponly=true; //Makes the cookie non readable through Javascript
                setCookie(TokenService::getSessionTokenName(), $token, $expire,null,null,$secure, $httponly);
                
                $data["success"]=0;
                $data["id"]=$user["id"];
                $data["role"]=$user["role"];

                return $data;
                //$data['token']=generateSessionToken(getAccountByUsername($_GET['username']));
            }else{
                $data["success"]=1;
                return $data;
            }
    }
    
    public static function provideCsrfToken(){
        $tokenPayload=TokenService::getSessionTokenPayload();
        if(!empty($tokenPayload['id'])){
            session_start();
            $_SESSION['CsrfToken'] = bin2hex(random_bytes(32));
            $token = $_SESSION['CsrfToken'];
            return $token;
        }else{
            //No valid token was passed
            return 'CSRF token providing failed, No valid token was passed';
        }
    }
    
    public static function revertAuthentication(){
        try {
            session_start();
            setcookie(TokenService::getSessionTokenName(), '', time() - 3600);
            $_SESSION['CsrfToken']=null;
            session_destroy();
            return 0;
        }
        catch(Exception $e) {
            return $e->getMessage();
        }
    
    }
    
    public static function permissionWrapper($allowedRoles, $callbackFunction, $callbackParameter){
        $tokenPayload=TokenService::getSessionTokenPayload();
        if(!empty($tokenPayload['id'])){
            session_start();
            if(!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($_SESSION['CsrfToken']
            && hash_equals($_SERVER['HTTP_X_CSRF_TOKEN'], $_SESSION['CsrfToken']))){
                $user=AccountsService::getAccountById($tokenPayload["id"]);
                if(!empty($user)){
                    if($allowedRoles=="all"){
                        return $callbackFunction($callbackParameter);
                    }elseif (in_array($user["role"], $allowedRoles)) {
                        return $callbackFunction($callbackParameter);
                    }else{
                        //User is not allowed to perform this action
                        return 'Validation failed, User is not allowed to perform this action';
                    }
                }else{
                    //User that requested the action does not exist anymore
                    return 'Validation failed, User that requested the action does not exist anymore';
                }
            }else{
                return "CSRF Token was not valid!";
            }
          }else{
            //No valid token was passed
            return 'Validation failed, No valid session token was passed';
          }
    }
    
    public static function requestNewPassword($username, $email){
        $user = AccountsService::getAccountByUsername($username);
        if(!empty($user) && $user['email']==$email){
            return PasswordResetService::sendPasswordResetMail(TokenService::generatePasswordResetToken($user), $user);
        }else{
            //Username doesn't exist or username and email mismatch occured
            return 1;
        }
    }
    
    public static function resetPassword($token, $password){
        $tokenPayload=TokenService::getPasswordResetTokenPayload($token);
        $timestamp=$tokenPayload["timestamp"];
        $user=AccountsService::getAccountById($tokenPayload["id"]);

        $isTokenValid=AccountsService::isPasswordResetTokenValid($user, $token);
        if($isTokenValid==true){
            //Token valid time is one hour = 3600 sec
            if(time()-3600<$timestamp){
                //Token/User/Time combination is valid
                AccountsService::resetPassword($user, $password);
                AccountsService::removePasswordResetToken($user);
                return 0;
            }else{
                //Token valid but too much time has passed
                //User has to request a new token
                //AccountsService::removePasswordResetToken($user);
                return 1;
            };
        }else{
            //Invalid token
            return 2;
        }
    }
}

?>