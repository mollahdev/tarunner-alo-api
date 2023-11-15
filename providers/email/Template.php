<?php 
namespace WP_SM_API\Providers\Email;

class Template {
    /**
     * Send email to user for account creation verification
     * @param int $user_id 
     */ 
    static function account_creation_verification( $user_id ) {
        $subject = 'Please verify your account';
        
        ob_start();
        include( WP_SM_API_DIR . 'providers/email/views/account-creation-verification.php' );
        $message = ob_get_clean();

        return [
            'subject' => $subject,
            'message' => $message
        ];
    }
}
