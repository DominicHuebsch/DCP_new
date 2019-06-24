<?php

require_once 'services/authenticate.service.php';

$case = "";

if (isset($_GET['username']) && isset($_GET['password']) && isset($_GET['rememberMe']))
	{
	$case = "authenticateUser";
	}
elseif (isset($_GET['username']) && isset($_GET['email']))
	{
	$case = "requestNewPassword";
	}
elseif (isset($_GET['token']) && isset($_GET['password']))
	{
	$case = "resetPassword";
	}
elseif (isset($_GET['csfrTokenRequest']))
	{
	$case = "provideCsfrToken";
	}
elseif (isset($_GET['logoutRequest']))
	{
	$case = "logout";
	}

switch ($case)
	{
case "authenticateUser":
	echo json_encode(AuthenticationService::authenticateUser($_GET['username'], $_GET['password'], $_GET['rememberMe']));
	break;

case "requestNewPassword":
	echo AuthenticationService::requestNewPassword($_GET['username'], $_GET['email']);
	break;

case "resetPassword":
	echo AuthenticationService::resetPassword($_GET['token'], $_GET['password']);
	break;

case "provideCsfrToken":
	//No JSON encode because it will put unnecessary "" around the token!
	echo AuthenticationService::provideCsrfToken();
	break;

case "logout":
	echo AuthenticationService::revertAuthentication();
	break;

default:
	echo "Invalid service invocation";
	break;
	}

?>