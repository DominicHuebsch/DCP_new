<?php

//This class provides functionality to send a password reset email using the phpMailer framework
//It reads the email HTML template from content.html in the same folder as this file and replaces
//certain contents in that tempate with variables generated in this class.
//
//When the template is prepared, the mail will be sent out using a preconfigured mailbox

//IMPORTANT!!! To make the email sending work, according daimler credentials have to be put in the mailbox configuration
//later in this code. The current implementation has been succesfully tested with gmail accounts. An ccording daimler
//mailbox and server configuration have to be put in here now.

//Include all files required for sending mails from the phpMailer framework
foreach (glob('services/email/phpMailer/*.php') as $filename) {
    require_once $filename;
}

//Include other required classes
require_once 'services/connect.service.php';
require_once 'services/accounts.service.php';

//Set the namespace to make phpMailer usable
use PHPMailer\PHPMailer\PHPMailer;

class PasswordResetService{
    //This is the only function of this class and uses a token (string) and a 
    //user object to generate the password reset email
    //The function returns the value 0 if the email was sent succesfully
    //If there is an error, the error detail will be forwarded to the front-end
    function sendPasswordResetMail($token, $user)
    {
        //Combines the password reset link that the user will click in the email later
        //It consists of the address of the front-end client and the path to the password reset mask
        //in combination with the provided token parameter
        $pwlink = ConnectionService::getFrontendAddress() . "resetpassword" ."/". $token;
        
        //Prepare the phpMailer
        $mail = new PHPMailer;
        $mail->isSMTP();
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug  = 0;

        //Configure the mailbox and mail server settings that is going to send the reset mail
        //This has to be changed to the proper daimler mailbox configuration
        $mail->Host       = 'email.server.com';
        $mail->Port       = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth   = true;
        $mail->Username   = "the.email.sending@address.com";
        $mail->Password   = "theAccordingPassword";

        //Options to configure how the mailbox should appear in the inbox of the receiver
        $mail->setFrom('dcp.service@daimler.com', 'DCP Service');
        $mail->addReplyTo('noreply@daimler.com', 'DCP Service');
        $mail->addAddress($user['email'], $user['firstname'] . " " . $user['lastname']);
        $mail->Subject = 'DCP Password Creation';
        
        //Read the email template
        $body = file_get_contents('services/email/passwordReset/content.html');

        //Replace certain values of the template with actual variables to personalize the mail
        $body = str_replace('$pwlink', $pwlink, $body);
        $body = str_replace('$firstname', $user['firstname'], $body);
        $body = str_replace('$lastname', $user['lastname'], $body);
        
        //Append the prepared template to the mail
        $mail->msgHTML($body);
        
        //Send the mail
        if (!$mail->send()) {
            //If an error occurs, the error will be forwarded to the front-end
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            //If everything worked, the sucess value 0 will be returned to the frond-end
            AccountsService::setPasswordResettoken($user, $token);
            return 0;
        }
    }
}



?>