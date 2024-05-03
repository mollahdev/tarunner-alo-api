<?php 
namespace Tarunner_Alo\Providers\Email;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

abstract class Config {
    protected $mail = null;
    function __construct() {
        $this->mail = new PHPMailer(true);
        $this->setup();
    }

    private function setup() {
        // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER; 
        $this->mail->isSMTP(); 
        $this->mail->Host = 'mailhog.test'; 
        $this->mail->SMTPAuth   = false;
        // $this->mail->Username   = 'user@example.com';
        // $this->mail->Password   = 'secret';
        // $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port       = 1025; 

        $this->mail->setFrom('invitationcentre@gmail.com', 'Invitation Centre');
        $this->mail->addReplyTo('invitationcentre+contact@gmail.com', 'Invitation Centre');
        $this->mail->isHTML(true);   
    }
}