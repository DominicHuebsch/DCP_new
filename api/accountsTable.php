<?php

require_once 'services/authenticate.service.php';
require_once 'services/authentication/token.service.php';
require_once 'services/accountsTable.service.php';

$case = "";

/* if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$json = file_get_contents('php://input');
	$getRequest = json_decode($json, true);
} */

if (isset($_GET['columnTitlesRequest'])){
	$case = "getAccountsTableColumnTitles";
	}
elseif (isset($_GET['filterDataRequest'])){
	$case = "getAccountsTableFilterData";
	}
elseif (isset($_GET['rowsRequest'])){
	$rowsRequest=json_decode($_GET['rowsRequest'], true);
	$case = "getAccountsTableRows";
	}
elseif (isset($_GET['rowCountRequest'])){
	$rowCountRequest=json_decode($_GET['rowCountRequest'], true);
	$case = "getAccountsTableRowCount";
	}

    
switch ($case){

		case "getAccountsTableColumnTitles":
			$callback=function($value){
				return AccountsTableService::getAccountsTableColumnTitles();
			};
			echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, null));
			break;
		
		case "getAccountsTableFilterData":
			$callback=function($value){
				return AccountsTableService::getAccountsTableFilterData();
			};
			echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, null));
			break;

		case "getAccountsTableRows":
			$callback=function($value){
				return AccountsTableService::getAccountsTableRows($value);
			};
			echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $rowsRequest));
			break;

		case "getAccountsTableRowCount":
			$callback=function($value){
				return AccountsTableService::getAccountsTableRowCount($value);
			};
			echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $rowCountRequest));
			break;

		default:
			echo "Invalid service invocation";
			break;
	}

?>