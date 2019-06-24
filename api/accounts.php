<?php

require_once 'services/authentication/token.service.php';
require_once 'services/authenticate.service.php';
require_once 'services/accounts.service.php';

$case = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$json = file_get_contents('php://input');
	$postRequest = json_decode($json, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	$json = file_get_contents('php://input');
	$putRequest = json_decode($json, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	$json = file_get_contents('php://input');
	$deleteRequest = json_decode($json, true);
}

if (isset($postRequest['user'])){
		$case = "createAccount";
	}
elseif (isset($putRequest['user'])){
		$case = "editAccount";
	}
elseif (isset($putRequest['password'])){
		$case = "editAccountPassword";
	}
elseif (isset($putRequest['ownUser'])){
		$case = "editOwnAccount";
	}
elseif (isset($putRequest['ownPassword'])){
		$case = "editOwnPassword";
	}
elseif (isset($deleteRequest['user'])){
		$case = "removeAccount";
	}
elseif (isset($_GET['id'])){
	$case = "getAccountById";
	}
elseif (isset($_GET['username'])){
	$case = "getAccountByUsername";
	}
elseif (isset($_GET['ownAccount'])){
	$case = "getOwnAccount";
	}

switch ($case)
	{
		case "createAccount":
			$callback=function($value){
				return AccountsService::createAccount($value);
			};
			echo AuthenticationService::permissionWrapper(array("1"), $callback, $postRequest['user']);
			break;

		case "editAccount":
			$callback=function($value){
				return AccountsService::editAccount($value);
			};
			echo AuthenticationService::permissionWrapper(array("1"), $callback, $putRequest['user']);
			break;

		case "editAccountPassword":
			$callback=function($value){
				return AccountsService::editAccountPassword($value);
			};
			echo AuthenticationService::permissionWrapper(array("1"), $callback, $putRequest['password']);
			break;

		case "removeAccount":
			$callback=function($value){
				return AccountsService::removeAccount($value);
			};
			echo AuthenticationService::permissionWrapper(array("1"), $callback, $deleteRequest['user']);
			break;

		case "getAccountById":
			$callback=function($value){
				return AccountsService::getAccountById($value);
			};
			echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $_GET['id']));
			break;

		case "getAccountByUsername":
			$callback=function($value){
				return AccountsService::getAccountByUsername($value);
			};
			echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $_GET['username']));
			break;

		case "getOwnAccount":
			$callback=function($value){
				return AccountsService::getAccountById($value);
			};
			echo json_encode(AuthenticationService::permissionWrapper("all", $callback, TokenService::getSessionTokenPayload()['id']));
			break;

		case "editOwnAccount":
			$callback=function($value){
				return AccountsService::editOwnAccount($value);
			};
			echo AuthenticationService::permissionWrapper("all", $callback, $putRequest['ownUser']);
			break;

		case "editOwnPassword":
			$callback=function($value){
				return AccountsService::editOwnPassword($value);
			};
			echo AuthenticationService::permissionWrapper("all", $callback, $putRequest['ownPassword']);
			break;

		default:
			echo "Invalid service invocation";
			break;
	}

?>