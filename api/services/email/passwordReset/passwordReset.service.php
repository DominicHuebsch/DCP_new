<?php

//include all files required for sending mails
foreach (glob('services/email/phpMailer/*.php') as $filename) {
    require_once $filename;
}

require_once 'services/connect.service.php';
require_once 'services/accounts.service.php';

use PHPMailer\PHPMailer\PHPMailer;

class PasswordResetService{
    function sendPasswordResetMail($token, $user)
    {
        
        $pwlink = ConnectionService::getFrontendAddress() . "resetpassword" ."/". $token;
        
        $mail = new PHPMailer;
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug  = 0;
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth   = true;
        $mail->Username   = "dominic.huebsch.dh@gmail.com";
        $mail->Password   = "16081709nord1964wow";
        $mail->setFrom('dcp.service@daimler.com', 'DCP Service');
        $mail->addReplyTo('noreply@daimler.com', 'DCP Service');
        $mail->addAddress($user['email'], $user['firstname'] . " " . $user['lastname']);
        $mail->Subject = 'DCP Password Creation';
        
        $body = file_get_contents('services/email/passwordReset/content.html');
        $body = str_replace('$pwlink', $pwlink, $body);
        $body = str_replace('$firstname', $user['firstname'], $body);
        $body = str_replace('$lastname', $user['lastname'], $body);
        
        $mail->msgHTML($body);
        $mail->AltBody = 'This is a plain-text message body';
        
        if (!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            AccountsService::setPasswordResettoken($user, $token);
            return 0;
        }
    }
}



?>