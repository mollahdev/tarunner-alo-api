<?php 
namespace Tarunner_Alo\Providers\Sms;

class Template {
    /**
     * Send email to user for account creation verification
     * @param int $user_id 
     */ 
    static function account_creation_verification( $user_id ) {
        $account = get_user_by( 'id', $user_id );
        $full_name = $account->first_name . ' ' . $account->last_name;
        $verification_key = $account->user_activation_key;

        return "Dear $full_name,\nYour verification code is $verification_key\n\nBy Tarunner Alo";
    }
}
