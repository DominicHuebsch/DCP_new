<?php

require_once 'services/connect.service.php';
require_once 'services/authentication/token.service.php';

class AccountsService{

//ADMINSITRATIVE FUNCTIONS

public static function getAccountById($id)
{
    $conn = ConnectionService::getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data=null;
    //if($result->num_rows === 0) exit('No rows');
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
        $data                  = $record;
    }
    
    $stmt->close();
    return $data;
}

public static function getAccountByUsername($username)
{
    $conn = ConnectionService::getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?;");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $data=null;
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
        $data                  = $record;
    }
    
    $stmt->close();
    return $data;
}

public static function getAccountPassword($username)
{
    $user=AccountsService::getAccountByUsername($username);
    $conn = ConnectionService::getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `users`.`id` = ?;");
    $stmt->bind_param("s", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data=null;
    while ($row = $result->fetch_assoc()) {
        $data = $row['password'];
    }
    
    $stmt->close();
    return $data;
}

public static function createAccount($user)
{
    $conn = ConnectionService::getConnection();
    $stmt = $conn->prepare("INSERT INTO `users` (`username`, `email`, `firstname`, `lastname`, `companyname`, `partnercode`, `password`, `getemailfrom`, `role`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssi", $user['username'], $user['email'], $user['firstname'], $user['lastname'], $user['companyname'], $user['partnercode'], $user['password'], $user['getemailfrom'], $user['role']);
    if ($stmt->execute()) {
        $data = 0;
    } else {
        if ($stmt->errno == '1062') {
            $data = 1;
        } else {
            $data = $stmt->error;
        }
    }
    $stmt->close();
    
    return $data;
}

public static function editAccount($user)
{
    $conn = ConnectionService::getConnection();

    $stmt = $conn->prepare("UPDATE `users` SET `username`=?, `email`=?, `firstname`=?, `lastname`=?, `companyname`=?, `partnercode`=?, `getemailfrom`=?, `role`=?
    WHERE `users`.`id` = ?;");
    $stmt->bind_param("ssssssssi", $user['username'], $user['email'], $user['firstname'], $user['lastname'], $user['companyname'], $user['partnercode'], $user['getemailfrom'], $user['role'], $user['id']);
    
    if ($stmt->execute()) {
        $data = 0;
    } else {
        if ($stmt->errno == '1062') {
            $data = 1;
        } else {
            $data = $stmt->error;
        }
    }
    $stmt->close();
    
    return $data;
}

public static function editAccountPassword($user)
{
    $user['id'];
    $conn = ConnectionService::getConnection();

    $stmt = $conn->prepare("UPDATE `users` SET `password`=? WHERE `users`.`id` = ?;");
    $stmt->bind_param("si", $user['password'], $user['id']);

    if ($stmt->execute()) {
        $data = 0;
    } else {
        $data = $stmt->error;
    }
    $stmt->close();
    
    return $data;
}

public static function resetPassword($user, $password)
{
    $conn = ConnectionService::getConnection();
    $stmt = $conn->prepare("UPDATE `users` SET `password`=? WHERE `users`.`id` = ?;");
    $stmt->bind_param("ss", $password, $user['id']);
    if ($stmt->execute()) {
        $data = 0;
    } else {
        $data = $stmt->error;
    }
    $stmt->close();
    return $data;
}

public static function setPasswordResetToken($user, $token){
    $conn = ConnectionService::getConnection();
    $stmt = $conn->prepare("UPDATE `users` SET `pwresettoken`= ? WHERE `users`.`id` = ?;");
    $stmt->bind_param("ss", $token, $user['id']);
    if ($stmt->execute()) {
        $data = 0;
    } else {
        $data = $stmt->error;
    }
    $stmt->close();
    return $data;
}

public static function removePasswordResetToken($user){
    $conn = ConnectionService::getConnection();
    $stmt = $conn->prepare("UPDATE `users` SET `pwresettoken`= NULL WHERE `users`.`id` = ?;");
    $stmt->bind_param("s", $user['id']);
    if ($stmt->execute()) {
        $data = 0;
    } else {
        $data = $stmt->error;
    }
    $stmt->close();
    return $data;
}

public static function isPasswordResetTokenValid($user, $token){
    $conn = ConnectionService::getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `users`.`id` = ?;");
    $stmt->bind_param("s", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data=null;
    while ($row = $result->fetch_assoc()) {
        $data = $row['pwresettoken'];
    }
    $stmt->close();
    if(hash_equals($data, $token)){
        return true;
    }
    return false;
}

public static function removeAccount($user)
{
    $conn = ConnectionService::getConnection();
    $stmt = $conn->prepare("DELETE FROM `users` WHERE `users`.`id` = ?;");
        $stmt->bind_param("i", $user['id']);
    if ($stmt->execute()) {
        $data = 0;
    }else{
        $data = $stmt->error;
    }
    $stmt->close();
    
    return $data;
}







//NORMAL USER TRIGGERABLE FUNCTIONS

public static function editOwnAccount($ownUser)
{
    $ownId=TokenService::getSessionTokenPayload()['id'];
    $conn = ConnectionService::getConnection();

    $stmt = $conn->prepare("UPDATE `users` SET `username`=?, `email`=?, `firstname`=?, `lastname`=?, `companyname`=?, `partnercode`=?, `getemailfrom`=?, `role`=?
    WHERE `users`.`id` = ?;");
                    if (!$stmt->bind_param("ssssssssi", $ownUser['username'], $ownUser['email'], $ownUser['firstname'], $ownUser['lastname'], $ownUser['companyname'], 
                    $ownUser['partnercode'], $user['getemailfrom'] ,$ownUser['role'], $ownId)) {
                        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                    }

    if ($stmt->execute()) {
        $data = 0;
    } else {
        $data = $stmt->error;
    }
    $stmt->close();
    
    return $data;
}

public static function editOwnPassword($password)
{
    $ownId=TokenService::getSessionTokenPayload()['id'];
    $conn = ConnectionService::getConnection();

    $stmt = $conn->prepare("UPDATE `users` SET `password`=? WHERE `users`.`id` = ?;");
    $stmt->bind_param("si", $password, $$ownId);

    if ($stmt->execute()) {
        $data = 0;
    } else {
        $data = $stmt->error;
    }
    $stmt->close();
    
    return $data;
}



}

?>