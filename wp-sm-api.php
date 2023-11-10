<?php
namespace WP_SM_API;
use WP_SM_API\Controller\StudentController;

/*
* Plugin Name:       WP Student Management Api
* Description:       A Student Management Plugin that provides with Api
* Version:           1.0.0
* Requires at least: 6.0
* Requires PHP:      7.4
* Author:            Ashraf Mollah
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// add autoload
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
    
    // add time zone to dhaka
    date_default_timezone_set('Asia/Dhaka');

    new StudentController();
}
