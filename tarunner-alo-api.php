<?php
namespace Tarunner_Alo;
use Tarunner_Alo\Controller\StudentController;
use Tarunner_Alo\Controller\ServiceController;
use Tarunner_Alo\Controller\UserController;
use Tarunner_Alo\Model\UserModel;
use Tarunner_Alo\Controller\AppController;

/*
* Plugin Name:       Taruner Alo Api
* Description:       This is a custom plugin for Taruner Alo Api
* Version:           1.0.0
* Requires at least: 6.0
* Requires PHP:      7.4
* Author:            Ashraf Mollah
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// define plugin working directory
define( 'WP_SM_API_DIR', plugin_dir_path( __FILE__ ) );

// add autoload
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/user.php');
    
    // add time zone to dhaka
    date_default_timezone_set('Asia/Dhaka');

    // create user roles
    UserModel::create_roles();

    // init controllers
    new AppController();
    new UserController();
    new StudentController();
    new ServiceController();
}
