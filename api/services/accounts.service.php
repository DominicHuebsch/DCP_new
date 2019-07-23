<?php
//This class offers function to manipulate data related to user accounts stored within the DB
//The functions are quite basic and usually insert, update, delete or compare user account data
//Usually the functions return objects or status codes if a DB entry has been successfully updated

require_once 'services/connect.service.php';
require_once 'services/authentication/token.service.php';

class AccountsService {

    //Returns an account object by an id as integer
    public static function getAccountById($id) {
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = null;
        //if($result->num_rows === 0) exit('No rows');
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
            $data = $record;
        }
        $stmt->close();
        return $data;
    }

    //Returns an account object by a username as string
    public static function getAccountByUsername($username) {
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?;");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = null;
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
            $data = $record;
        }
        $stmt->close();
        return $data;
    }

    //validates if a provided user password matches with the record in the database
    //returns true if the passwords match
    //returns false if they don't match
    public static function validatePassword($username, $passwordFromClient) {
        $user = AccountsService::getAccountByUsername($username);
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `users`.`id` = ?;");
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $passwordFromDatabase = null;
        while ($row = $result->fetch_assoc()) {
            $passwordFromDatabase = $row['password'];
        }
        $stmt->close();
        if (hash_equals($passwordFromDatabase, $passwordFromClient)) {
            return true;
        }
        return false;
    }

    //Creates a new user account in the DB using the provided user object
    //Return 0 if the operation has been completed successfully
    //Throws the error code 1 if the username already exists
    //Throws a specific error if an unknown error occured
    public static function createAccount($user) {
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("INSERT INTO `users` (`username`, `email`, `firstname`, `lastname`, `companyname`, `partnercode`, `password`, `getemailfrom`, `role`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $user['username'], $user['email'], $user['firstname'], $user['lastname'], $user['companyname'], $user['partnercode'], $user['password'], $user['getemailfrom'], $user['role']);
        if ($stmt->execute()) {
            $data = 0;
        } else {
            //Error if username already exists
            if ($stmt->errno == '1062') {
                $data = 1;
            } else {
                $data = $stmt->error;
            }
        }
        $stmt->close();
        return $data;
    }

    //Edits an existing user account in the DB using the provided object
    //Return 0 if the operation has been completed successfully
    //Throws the error code 1 if the username already exists
    //Throws a specific error if an unknown error occured
    public static function editAccount($user) {
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("UPDATE `users` SET `username`=?, `email`=?, `firstname`=?, `lastname`=?, `companyname`=?, `partnercode`=?, `getemailfrom`=?, `role`=? WHERE `users`.`id` = ?;");
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

    //Edits the password of a user through using an updated user object containing the new password
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function editAccountPassword($user) {
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

    //Resets the password for a not logged in user after the user clicked the appropriate link in the reset e-mail
    //The password is choosable by the user itself using an appropriate front-end mask
    //The function therefore takes the current user and inserts the newly setted password by the user
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function resetPassword($user, $password) {
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

    //Sets a new password reset token for a user account in the DB. This token will be used for the password reset process
    //and will be configured to have one time use only and will be usable for one hour as implemented and described in authenticate.service.php
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function setPasswordResetToken($user, $token) {
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

    //Removes the password reset token from a user account if the password reset process has been succesful to ensure one time use
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function removePasswordResetToken($user) {
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

    //Verifies if the token provided by a user within the password reset mail matches with the token in the DB
    //Returns true if the tokens match
    //Returns false the tokens don't match
    public static function isPasswordResetTokenValid($user, $token) {
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `users`.`id` = ?;");
        $stmt->bind_param("s", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = null;
        while ($row = $result->fetch_assoc()) {
            $data = $row['pwresettoken'];
        }
        $stmt->close();
        if (hash_equals($data, $token)) {
            return true;
        }
        return false;
    }

    //Removes a user account from the DB using the provided user object
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function removeAccount($user) {
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("DELETE FROM `users` WHERE `users`.`id` = ?;");
        $stmt->bind_param("i", $user['id']);
        if ($stmt->execute()) {
            $data = 0;
        } else {
            $data = $stmt->error;
        }
        $stmt->close();
        return $data;
    }

    //Provides a function that a user can edit its own account using an updated user account object
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function editOwnAccount($ownUser) {
        $ownId = TokenService::getSessionTokenPayload() ['id'];
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("UPDATE `users` SET `username`=?, `email`=?, `firstname`=?, `lastname`=?, `companyname`=?, `partnercode`=?, `getemailfrom`=? WHERE `users`.`id` = ?;");
        if (!$stmt->bind_param("sssssssi", $ownUser['username'], $ownUser['email'], $ownUser['firstname'], $ownUser['lastname'], $ownUser['companyname'], $ownUser['partnercode'], $ownUser['getemailfrom'], $ownId)) {
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
    
    //Provides a function that a user can edit its own password using the provided password as string
    //Return 0 if the operation has been completed successfully
    //Throws a specific error if an unknown error occured
    public static function editOwnPassword($password) {
        $ownId = TokenService::getSessionTokenPayload() ['id'];
        $conn = ConnectionService::getConnection();
        $stmt = $conn->prepare("UPDATE `users` SET `password`=? WHERE `users`.`id` = ?;");
        $stmt->bind_param("si", $password, $ownId);
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