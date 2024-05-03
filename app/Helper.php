<?php
namespace Tarunner_Alo\App;

class Helper {
    static function generate_username( $first_name, $last_name ) {
       // Combine first and last name
        $username = strtolower($first_name . $last_name);

        // Remove spaces and special characters
        $username = preg_replace('/[^a-z0-9]/', '', $username);

        // Check if the username already exists
        $suffix = 1;
        $original_username = $username;

        while (username_exists($username)) {
            $username = $original_username . $suffix;
            $suffix++;
        }

        return $username;
    }

    static function generate_expiration_time( $days = 1 ) {
        return strtotime( '+' . $days . ' days' );
    }
}