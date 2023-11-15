<?php 
namespace WP_SM_API\Providers\Email;
use WP_SM_API\App\Singleton;

class Mail extends Config{
    use Singleton;

    function send( $to, $template ) {
        $subject = $template['subject'];
        $message = $template['message'];

        try {
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $message;
            $this->mail->send();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}