<?php
//Broker that consumes requests sent by the front end for
//accessing and manipulating data of user accounts
//After the broker consumed and identified a request, it gets forwarded to a matching
//function of a succeeding subcomponent represented by a mapper class
//
//Including of required classes
require_once 'services/authentication/token.service.php';
require_once 'services/authenticate.service.php';
require_once 'services/accounts.service.php';
//
//The following if-blocks consume parameter objects passed
//by the front-end if a user needs to be created, updated or deleted
//using POST, PUT or DELETE requests.
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $postRequest = json_decode($json, true);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json = file_get_contents('php://input');
    $putRequest = json_decode($json, true);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $json = file_get_contents('php://input');
    $deleteRequest = json_decode($json, true);
}
//
//Due to which specific CRUD Operation was requested, one of the following if-blocks is invoked.
//Each of the blocks is making use of the AuthenticationService class which checks
//if the request contains appropriate privileges before being executed.
//
//As an example how each of the switch-case blocks works, the first if-block for creating a user will be explained more in detail
if (isset($postRequest['user'])) {
    //Prepare a $callback-variable that will store the actual function for creating an account to be executed later if the permission check was succesful
    //This $callback variable holds back the execution of the function until the permission check is finished.
    //The $value variable will be used to pass the user object of the front end to the later executed function within $callback variable
    $callback = function ($value) {
        //The actual function to create an account. It will receive the user object of the front end later using the $value variable
        return AccountsService::createAccount($value);
    };
    //Now the permission wrapper for the AuthenticationService class needs three variables in order to
    //check a request for its validity before executing the requested function
    //	1. A variable containing information about which user roles are allowed to perform a secured operation. This can be whether...
    //		- ...a string "all" which would make every authenticated user allowed to perform the function
    //		- ...an array containg the user roles as strings (e.g array("1,2") would make administrators and staff eligible for the action).
    //	2. The $callbackFunction variable which contains the actual business logic function to be executed after succesful security validation
    //	3. Any parameter needed by the business logic function in $callback. It this case this is the transferred user object by the front end.
    //
    //The permission wrapper will now perform the following actions:
    //	1. Check if a valid authentication token was provided within the request thorugh a browser cookie.
    //	2. Check if a valid CSRF token was provided within the request through the client-front end.
    //	3. Check if the user account that put the request still exist in the DB.
    //	4. Check if the user account that put the request has the appropriate role level as specified (1,2,3 / all).
    //	5. If all the previous checks passed, the actual function AccountsService::createAccount will get executed using the $postRequest['user'] parameter
    echo AuthenticationService::permissionWrapper(array("1"), $callback, $postRequest['user']);
    exit();

//Updates a user account triggered by an administrator
} elseif (isset($putRequest['user'])) {
    $callback = function ($value) {
        return AccountsService::editAccount($value);
    };
    echo AuthenticationService::permissionWrapper(array("1"), $callback, $putRequest['user']);
    exit();

//Updates the password triggered by an administrator
} elseif (isset($putRequest['password'])) {
    $callback = function ($value) {
        return AccountsService::editAccountPassword($value);
    };
    echo AuthenticationService::permissionWrapper(array("1"), $callback, $putRequest['password']);
    exit();

//Updates the own account triggered by any user
} elseif (isset($putRequest['ownUser'])) {
    $callback = function ($value) {
        return AccountsService::editOwnAccount($value);
    };
    echo AuthenticationService::permissionWrapper("all", $callback, $putRequest['ownUser']);
    exit();

//Updates the own password triggered by any user
} elseif (isset($putRequest['ownPassword'])) {
    $callback = function ($value) {
        return AccountsService::editOwnPassword($value);
    };
    echo AuthenticationService::permissionWrapper("all", $callback, $putRequest['ownPassword']);
    exit();

//Deletes a user account triggered by an administrator
} elseif (isset($deleteRequest['user'])) {
    $callback = function ($value) {
        return AccountsService::removeAccount($value);
    };
    echo AuthenticationService::permissionWrapper(array("1"), $callback, $deleteRequest['user']);
    exit();

//Returns a user account by an id triggered by an administrator
} elseif (isset($_GET['id'])) {
    $callback = function ($value) {
        return AccountsService::getAccountById($value);
    };
    echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $_GET['id']));
    exit();

//Returns a user account by a username triggered by an administrator
} elseif (isset($_GET['username'])) {
    $callback = function ($value) {
        return AccountsService::getAccountByUsername($value);
    };
    echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $_GET['username']));
    exit();

//Returns the own user account triggered by any user
} elseif (isset($_GET['ownAccount'])) {
    $callback = function ($value) {
        return AccountsService::getAccountById($value);
    };
    echo json_encode(AuthenticationService::permissionWrapper("all", $callback, TokenService::getSessionTokenPayload() ['id']));
    exit();

//If non of the previous checks were valid, the broker has been invocated with wrong parameters
} else {
    echo "Invalid service invocation";
    exit();
}
?>