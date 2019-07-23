<?php

//This class provides functionality to request data to build a filterable account list at the front-end
//The functions are close to the implementation of the front-end and under certain circumstances not usable for other front-end implementations
//A detailed documentation about how the filterable list will be embedded into a table view can be found in the front-end implementation for datatables.

require_once 'services/connect.service.php';

class AccountsTableService {

    //This function returns order types as string representations within an array
    //This is used to improve security for prepared statements to prevent SQL-injections
    function getAccountsTableOrderTypeList() {
        return array("asc", "desc");
    }

    //Returns an array containing the column titles that will be filterable later to display the filter options in the front-end
    function getAccountsTableFilterColumns() {
        return array('companyname', 'role');
    }

    //Returns an array representing all columns of that filterable account list to be displayed as a table
    function getAccountsTableColumnTitles() {
        //This array will define the order and type of incoming client-values for the upcoming SQL-Statements in the WHERE-Clause
        //It has to match the order and type in the according front end JSON-Language-File in the frontend!
        return array('username', 'email', 'firstname', 'lastname', 'companyname', 'partnercode', 'getemailfrom', 'role');
    }

    //Function to determine all dinstinct(!) values of columns that should be filterable
    //in order to build a visual filter option using a selectbox in the frontend
    function getAccountsTableFilterData() {
        //Get the connection to DB
        $conn = ConnectionService::getConnection();
        //Get the filterable columns as defined in the getAccountsTableFilterColumns() function
        $filterColumns = AccountsTableService::getAccountsTableFilterColumns();
        //Prepare the result array. It will contain an element for each column that should be filterable.
        //Each of those elements will be an array in turn containing the column name in the first element
        //and a list of all distinct values of that column in the second element (another array).
        $data = array();
        //For each column to be filtered, there will be a seperate SQL-Query
        for ($i = 0;$i < sizeOf($filterColumns);$i++) {
            //Statement to get the distinct values of a column
            $stmt = $conn->prepare("SELECT DISTINCT $filterColumns[$i] FROM users;");
            //Execute the prepared Statement
            $stmt->execute();
            //Retrieve the result
            $result = $stmt->get_result();
            //Prepare an array to store the values of the current column
            $values = array();
            //As long as there are still values within the result, copy them into the values array
            while ($row = $result->fetch_assoc()) {
                $values[] = $row[$filterColumns[$i]];
            }
            //Close the connection
            $stmt->close();
            //Prepare an array that will store the name of the current column and the just retrieved values
            $record = array("columnTitle" => $filterColumns[$i], "values" => $values,);
            //Store that array into the final result array
            $data[] = $record;
        }
        //Return the final array containing all data
        return $data;
    }

    //This functions returns a list of accounts due to configured filter options stored in the $tableRowsRequest parameter
    //In combination with all of the data passed by the parameter, the result of this function won't be the complete list but
    //a "page" of that list displayed in a table. This enables server side pagination through returning only a specific page to the front-end
    //in combination with filters, search options, rows to show,...
    function getAccountsTableRows($tableRowsRequest) {
        $conn = ConnectionService::getConnection(); //Get the DB connection
        $getAccountsTableColumnTitles = AccountsTableService::getAccountsTableColumnTitles(); //Retrieve all available column titles of the list
        $orderBy = $tableRowsRequest['orderBy']; //Contains a string by which column the list will be sorted e.g. "username"
        $orderType = $tableRowsRequest['orderType']; //Contains a string about the order type of the selected column e.g. "desc"
        $search = $tableRowsRequest['search']; //Contains a search string to only show list elements containing that string (Can be empty)
        $filter = $tableRowsRequest['filter']; //Contains an array storing data about which columns have filters applied
        $currentRow = $tableRowsRequest['currentRow']; //An int variable representing which page of the filtered list should be shown to enable pagination in the front-end
        $rowsToShow = $tableRowsRequest['rowsToShow']; //Contains an int variable to limit how much entries should be shown in this page
        //Puts wildcards (%) before and after the search string to prepare the string for SQL-statements
        $search = ConnectionService::toWildcard($search);
        //Prepares an empty string with wildcards for the SQL-statement
        $filterWildcard = ConnectionService::toWildcard("");
        //Checks if the parameter for the preferred order type matches with the available options as specified in this class
        if (in_array($orderType, AccountsTableService::getAccountsTableOrderTypeList())) {
            //Checks if the orderby value is valid
            if (in_array($orderBy, AccountsTableService::getAccountsTableColumnTitles())) {
                //The following prepared SQL statements works as follows:
                //  1. It filters all values according to the given search parameter
                //  2. If there exist a filter for the role and/or companyname column, the result is filtered by the passed value
                //         -If no filter values are passed for that columns, the filter is left by a blank value and won't be applied
                //  3. The result is ordered asc/desc in combination with a selected column
                //  4. The result is limited using the current row pointer and the amount of rows to show
                if (!$stmt = $conn->prepare("SELECT * FROM users WHERE 
                (`firstname` LIKE ? OR
                `lastname` LIKE ? OR 
                `email` LIKE ? OR 
                `partnercode` LIKE ? OR 
                `username` LIKE ? OR 
                `role` LIKE ? OR
                `getemailfrom` LIKE ? OR 
                `companyname` LIKE ?) 
                AND( 
                    IF((SELECT COUNT(*) FROM users WHERE `role` = ?)>0,`role` = ?,`role` LIKE ?) AND 
                    IF((SELECT COUNT(*) FROM users WHERE `companyname` = ?)>0,`companyname` = ?,`companyname` LIKE ?)
                    )
                ORDER BY $orderBy $orderType LIMIT ?,?;")) {
                    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                }
                //Preparing the statement values
                if (!$stmt->bind_param("ssssssssssssssii", $search, $search, $search, $search, $search, $search, $search, $search, $filter['role'], $filter['role'], $filterWildcard, $filter['companyname'], $filter['companyname'], $filterWildcard, $currentRow, $rowsToShow)) {
                    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                //Execute the statement
                if (!$stmt->execute()) {
                    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                $result = $stmt->get_result();
                //Prepare the array to be returned
                $data = array();
                //For every row in the result set, put the values into a sperate array and add them into the final $data array
                while ($row = $result->fetch_assoc()) {
                    $record['id'] = $row['id'];
                    $record['partnercode'] = $row['partnercode'];
                    $record['email'] = $row['email'];
                    $record['username'] = $row['username'];
                    $record['firstname'] = $row['firstname'];
                    $record['lastname'] = $row['lastname'];
                    $record['companyname'] = $row['companyname'];
                    $record['getemailfrom'] = $row['getemailfrom'];
                    $record['role'] = (int)$row['role'];
                    $data[] = $record;
                }
            } else {
                $data = "Invalid order by parameters";
            }
        } else {
            $data = "Invalid order by arguments";
        }
        $stmt->close();
        return $data;
    }
    
    //This function works almost the same as getAccountsTableRows($tableRowsRequest) but doesn't return the actual accounts
    //as a list but the amount of filtered rows as integer value to display it in the frontend. This represents the summed(!) amount of all
    //list elements according to applied filters without considering pagination. The frontend will then display something like
    //Showing 1-10 items of 76 items wheras this function returns the value 76.
    function getAccountsTableRowCount($rowCountRequest) {
        //Get the connection
        $conn = ConnectionService::getConnection();
        //Obtain search and filter arguments stored in $rowCountRequest
        //More arguments are not necessary to count the amount of entries/rows
        $search = $rowCountRequest['search'];
        $filter = $rowCountRequest['filter'];
        //Apply wildcards for SQL statements
        $search = ConnectionService::toWildcard($search);
        $filterWildcard = ConnectionService::toWildcard("");
        //The same statement as explained in getAccountsTableRows($tableRowsRequest) except that not the actual values are returned
        //but the amount of them. This is achieved through wrapping a SELECT COUNT(*) around the whole statement
        if (!$stmt = $conn->prepare("SELECT COUNT(*) FROM (SELECT * FROM users WHERE 
        (`firstname` LIKE ? OR 
        `lastname` LIKE ? OR 
        `email` LIKE ? OR 
        `partnercode` LIKE ? OR 
        `username` LIKE ? OR 
        `role` LIKE ? OR 
        `getemailfrom` LIKE ? OR 
        `companyname` LIKE ?)
        AND( 
            IF((SELECT COUNT(*) FROM users WHERE `role` = ?)>0,`role` = ?,`role` LIKE ?) AND 
            IF((SELECT COUNT(*) FROM users WHERE `companyname` = ?)>0,`companyname` = ?,`companyname` LIKE ?)
            )) as result;")) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        }
        //Prepare the statement values
        if (!$stmt->bind_param("ssssssssssssss", $search, $search, $search, $search, $search, $search, $search, $search, $filter['role'], $filter['role'], $filterWildcard, $filter['companyname'], $filter['companyname'], $filterWildcard)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        //Execute the statement
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        //Retrieve the result
        $result = $stmt->get_result();
        //Store the result as int value and return it to the client
        $data = 0;
        if ($row = $result->fetch_assoc()) {
            $data = (int)$row['COUNT(*)'];
        }
        $stmt->close();
        return $data;
    }
}
?>