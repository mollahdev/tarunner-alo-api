<?php 
namespace Tarunner_Alo\Providers\Sms;
use Tarunner_Alo\App\Singleton;

class Sms extends Config{
    use Singleton;

    function send( $to, $message ) {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.sms.net.bd/sendsms',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'api_key' => $this->token,
                    'msg' => $message,
                    'to' => $to
                ),
            ));

            curl_exec($curl);
            curl_close($curl);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}