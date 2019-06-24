<?php

require_once 'services/connect.service.php';

class AccountsTableService{

    function getAccountsTableOrderTypeList()
    {
        return array(
            "asc",
            "desc"
        );
    }
    
    function getAccountsTableFilterColumns(){
        return array(
            'companyname',
            'role'
        );
    }
    
    function getAccountsTableColumnTitles()
    {
        //This array will define the order and type of incoming client-values
        //for the upcoming SQL-Statements in the WHERE-Clause
        //It has to match the order and type in the according front end JSON-Language-File
        //in the frontend!
        return array(
            'username',
            'email',
            'firstname',
            'lastname',
            'companyname',
            'partnercode',
            'getemailfrom',
            'role'
        );
    }
    
    
    function getAccountsTableFilterData(){
        $conn = ConnectionService::getConnection();
        $filterColumns=AccountsTableService::getAccountsTableFilterColumns();
        $data=array();
        for($i=0; $i<sizeOf($filterColumns);$i++){
            $stmt = $conn->prepare("SELECT DISTINCT $filterColumns[$i] FROM users;");
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $values = array();
            while ($row = $result->fetch_assoc()) {
                $values[] = $row[$filterColumns[$i]];
            }
            $stmt->close();
    
            $record=array(
                "columnTitle" => $filterColumns[$i],
                "values" => $values,
            );
            $data[]=$record;
        }
        return $data;
    }
    
    function getAccountsTableRows($tableRowsRequest)
    {
    
        $conn       = ConnectionService::getConnection();
        $getAccountsTableColumnTitles = AccountsTableService::getAccountsTableColumnTitles();
        $orderBy    = $tableRowsRequest['orderBy'];
        $orderType  = $tableRowsRequest['orderType'];
        $search     = $tableRowsRequest['search'];
        $filter     = $tableRowsRequest['filter'];
        $currentRow = $tableRowsRequest['currentRow'];
        $rowsToShow = $tableRowsRequest['rowsToShow'];
        
        $search=ConnectionService::toWildcard($search);
        $filterWildcard=ConnectionService::toWildcard("");
        
    
        if (in_array($orderType, AccountsTableService::getAccountsTableOrderTypeList())) {
            if (in_array($orderBy, AccountsTableService::getAccountsTableColumnTitles())) {
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
    
                if (!$stmt->bind_param("ssssssssssssssii", 
                $search, $search, $search, $search, $search, $search, $search, $search,
                $filter['role'], $filter['role'], $filterWildcard, $filter['companyname'], $filter['companyname'], $filterWildcard, 
                $currentRow, $rowsToShow)) {
                    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                
                if (!$stmt->execute()) {
                    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                }
                $result = $stmt->get_result();
                
                $data = array();
                while ($row = $result->fetch_assoc()) {
                    $record['id']          = $row['id'];
                    $record['partnercode'] = $row['partnercode'];
                    $record['email']       = $row['email'];
                    $record['username']    = $row['username'];
                    $record['firstname']   = $row['firstname'];
                    $record['lastname']    = $row['lastname'];
                    $record['companyname'] = $row['companyname'];
                    $record['getemailfrom'] = $row['getemailfrom'];
                    $record['role']        = (int) $row['role'];
                    $data[]                = $record;
                }
            }
        } else {
            $data = "Invalid arguments for getTableRow()";
        }
        
        $stmt->close();
        return $data;
    }
    
    function getAccountsTableRowCount($rowCountRequest)
    {
        $conn = ConnectionService::getConnection();
        $search     = $rowCountRequest['search'];
        $filter     = $rowCountRequest['filter'];
    
        $search=ConnectionService::toWildcard($search);
        $filterWildcard=ConnectionService::toWildcard("");
        
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
    
        if (!$stmt->bind_param("ssssssssssssss", 
        $search, $search, $search, $search, $search, $search, $search, $search,
        $filter['role'], $filter['role'], $filterWildcard, $filter['companyname'], $filter['companyname'], $filterWildcard)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        
        $result = $stmt->get_result();
        $data   = 0;
        if ($row = $result->fetch_assoc()) {
            $data = (int) $row['COUNT(*)'];
        }
        $stmt->close();
        return $data;
    }
}






/* SELECT * FROM users 
            WHERE `username` LIKE "%%"
            AND `email` LIKE "%%"
            AND `firstname` LIKE "%%" 
            AND `lastname` LIKE "%%"
            AND `companyname` LIKE "%%"
            AND `partnercode` LIKE "%%"
            AND `role` LIKE "%%"
            ORDER BY `username` asc LIMIT 0,10 */

/*             SELECT * FROM (SELECT * FROM users 
            WHERE `role` LIKE 0
            AND `companyname` LIKE "%%") as result WHERE
            `firstname` LIKE "%test%" 
            OR `lastname` LIKE "%test%"
            OR `email` LIKE "%test%"
            OR `partnercode` LIKE "%test%"
            OR `username` LIKE "%test%"
            ORDER BY `username` asc LIMIT 0,10 */

/*             SELECT COUNT(*) FROM (
                SELECT * FROM (
                    SELECT * FROM users WHERE `role` LIKE "%%" AND `companyname` LIKE "%%") as filterresult WHERE `firstname` LIKE "%%" 
            OR `lastname` LIKE "%%"
            OR `email` LIKE "%%"
            OR `partnercode` LIKE "%%"
            OR `username` LIKE "%%") as result; */

/*             SELECT * FROM users 
            WHERE `firstname` LIKE "%%" 
             OR `lastname` LIKE "%%"
             OR `email` LIKE "%%"
             OR `partnercode` LIKE "%%"
             OR `username` LIKE "%%"
             OR `role` LIKE "%%"
             OR `companyname` LIKE "%%"
             ORDER BY username asc LIMIT 0,10; */
/* 

/* SELECT * FROM users WHERE 
                (`firstname` LIKE '%%' OR 
                `lastname` LIKE '%%' OR 
                `email` LIKE '%%' OR 
                `partnercode` LIKE '%%' OR 
                `username` LIKE '%%' OR 
                `role` LIKE '%%' OR 
                `companyname` LIKE '%%') 
                AND( 
                    IF((SELECT COUNT(*) FROM users WHERE `role` = null)>0,`role` = null,`role` LIKE '%%') AND 
                    IF((SELECT COUNT(*) FROM users WHERE `companyname` = '$$noFilterActive$$')>0,`companyname` = '$$noFilterActive$$',`companyname` LIKE '%%')
                    )
                ORDER BY 'username' 'asc' LIMIT 0,10; */
?>