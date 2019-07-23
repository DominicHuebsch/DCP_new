<?php
//
//This file is the first broker an unauthenticated client will contact
//It provides basic function to interact will unknown users as login request, new pw request,...
//As no of the preceeding actions needs an request validation, the functions are available to use to everyone
//
//It is important to mention that an actual login/logout with the web site doesn't exist. In the style of REST-services,
//this service provides tokens to successfully authenticated clients which they need to show to this service everytime they want
//to perform a secured operation. According to that, this broker emits CSFR-tokens to the front-end to prevent site forging.
//The front-end automatically will send the gathered CSRF-token with every request to validate that any request
//is performed by the actual front-end.
//
require_once 'services/authenticate.service.php';
//stores a security token in a browser cookie after succesful authentication to this service
//Returns a JSON-object containing data about the status if the authentication was succesful or not
if (isset($_GET['username']) && isset($_GET['password']) && isset($_GET['rememberMe'])) {
    echo json_encode(AuthenticationService::authenticateUser($_GET['username'], $_GET['password'], $_GET['rememberMe']));
    exit();

//Triggers the reset password process via mail
} elseif (isset($_GET['username']) && isset($_GET['email'])) {
    echo AuthenticationService::requestNewPassword($_GET['username'], $_GET['email']);
    exit();

//sets a new password for a user in combination with a textual token received via mail link
} elseif (isset($_GET['token']) && isset($_GET['password'])) {
    echo AuthenticationService::resetPassword($_GET['token'], $_GET['password']);
    exit();

//Sends the CSFR-token to the front-end for authenticated users
} elseif (isset($_GET['csfrTokenRequest'])) {
    echo AuthenticationService::provideCsrfToken();
    exit();

//Triggers the deletion of the user token to revert an authentication (logout for the front-end)
} elseif (isset($_GET['logoutRequest'])) {
    echo AuthenticationService::revertAuthentication();
    exit();

//If non of the previous checks were valid, the broker has been invocated with wrong parameters
} else {
    echo "Invalid service invocation";
    exit();
}
?>
