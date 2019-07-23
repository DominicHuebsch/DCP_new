<?php
//Broker that consumes requests sent by the front end for
//requesting data to generate a filterable account list for the front-end
//After the broker consumed and identified a request, it gets forwarded to a matching
//function of a succeeding subcomponent represented by a mapper class
//All the data that is provided by this broker will be used to generate an account overview table
//for administrators in the front-end
//
//Including of required classes
require_once 'services/authenticate.service.php';
require_once 'services/authentication/token.service.php';
require_once 'services/accountsTable.service.php';
//
//Due to which specific specific data for the filterable list is required, one of the
//following if-blocks is invoked. Each of the requests is GET-only and only retrieves data from the backend.
//Each of the blocks is making use of the AuthenticationService class which checks
//if the request contains appropriate privileges before being executed.
//
//The logic of the permission validation in conjunction with the function execution
//is working alike as descirbed in accounts.php

//Returns the column titles for the account table
if (isset($_GET['columnTitlesRequest'])) {
    $callback = function ($value) {
        return AccountsTableService::getAccountsTableColumnTitles();
    };
    echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, null));
    exit();

//Returns an array usable to configure and apply filters in the front-end
} elseif (isset($_GET['filterDataRequest'])) {
    $callback = function ($value) {
        return AccountsTableService::getAccountsTableFilterData();
    };
    echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, null));
    exit();

//Returns a table page containing a list of user accounts due to applied filters and search
} elseif (isset($_GET['rowsRequest'])) {
    $rowsRequest = json_decode($_GET['rowsRequest'], true);
    $callback = function ($value) {
        return AccountsTableService::getAccountsTableRows($value);
    };
    echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $rowsRequest));
    exit();

//Returns the complete amount of account entries due to applied filters and search
} elseif (isset($_GET['rowCountRequest'])) {
    $rowCountRequest = json_decode($_GET['rowCountRequest'], true);
    $callback = function ($value) {
        return AccountsTableService::getAccountsTableRowCount($value);
    };
    echo json_encode(AuthenticationService::permissionWrapper(array("1"), $callback, $rowCountRequest));
    exit();

//If non of the previous checks were valid, the broker has been invocated with wrong parameters
} else {
    echo "Invalid service invocation";
    exit();
}
?>