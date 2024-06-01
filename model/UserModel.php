<?php 
namespace Tarunner_Alo\Model;

use Tarunner_Alo\App\Singleton;
use Tarunner_Alo\App\Helper;
use Exception;

class UserModel extends BaseModel
{
    use Singleton;
    function __construct() {
        // hook on deleting user
        add_action('delete_user', [$this, 'on_delete_user']);
    }

    function get_post_type() {
        return 'tarunner_alo_user';
    }
    /**
     * create user
     * @param array $params 
     */ 
    function create_user( $params = [] ) {
        $role = $params['role'];

        if( !get_role( $role ) ) {
            throw new Exception('Role does not exist');
        }

        if( email_exists( $params['email'] ) ) {
            throw new Exception('Email already exists');
        }

        // check if phone number already exists
        $user = get_users([
            'meta_key' => 'phone',
            'meta_value' => $params['phone'],
            'number' => 1,
            'fields' => 'ID'
        ]);

        if( $user ) {
            throw new Exception('Phone number already exists');
        }

        // generate username
        $params['username'] = Helper::generate_username($params['first_name'], $params['last_name']);

        //random number of 6 digits
        $random = rand(100000, 999999);

        // insert user
        $id = wp_insert_user([
            'first_name'    => $params['first_name'],
            'last_name'     => $params['last_name'],
            'user_email'    => $params['email'],
            'user_pass'     => $params['password'],
            'role'          => $params['role'],
            'user_login'    => $params['username'],
            'user_activation_key' => $random,
            'meta_input'    => [
                'phone'         => $params['phone'],
                'country_code'  => $params['country_code'],
                'date_of_birth' => $params['date_of_birth'],
                "plan_id"       => $params['plan_id'],
                'is_phone_verified' => 'no',
                'is_email_verified' => 'no',
                'avatar'        => null,
                'blood_group'   => $params['blood_group'],
                'location'      => $params['location'],
                'status'        => 'pending', // active, inactive, pending
            ] 
        ]);

        if( is_wp_error($id) ) {
            throw new Exception($id->get_error_message());
        }

        // upload avatar
        $attachment_id = media_handle_upload('avatar', $id);
        if (is_wp_error($attachment_id)) {
            // delete user
            wp_delete_user($id);
            throw new Exception($attachment_id->get_error_message());
        }
        // update post meta
        update_user_meta($id, 'avatar', $attachment_id);

        $params['id'] = $id;
        $params['avatar'] = wp_get_attachment_url($attachment_id);
        
        return $params;
    }
    
    /**
     * create user roles
     * @return void 
     */ 
    static function create_roles() {
        $roles = [
            'member' => 'Member',
            'editor' => 'Editor',
            'admin' => 'Admin',
            'guest' => 'Guest',
        ];

        $common = [
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
        ];

        foreach ($roles as $key => $value) {
            // check if role already exists
            if( !get_role( $key ) ) {
                add_role( $key, $value, $common );
            }
        }
    }

    /**
     * get user details by id 
     */ 
    static function get_user_data( $id ) {
        $user = get_user_by('id', $id);
        if(!$user) {
           return false;
        }
        
        $data = [
            'id' => $user->ID,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'role' => $user->roles[0],
            'phone' => get_user_meta($user->ID, 'phone', true),
            'country_code' => get_user_meta($user->ID, 'country_code', true),
            'date_of_birth' => get_user_meta($user->ID, 'date_of_birth', true),
            'avatar' => wp_get_attachment_url(get_user_meta($user->ID, 'avatar', true)),
            'is_phone_verified' => get_user_meta($user->ID, 'is_phone_verified', true),
            'is_email_verified' => get_user_meta($user->ID, 'is_email_verified', true),
            'plan_id' => get_user_meta($user->ID, 'plan_id', true),
            'blood_group' => get_user_meta($user->ID, 'blood_group', true),
            'location' => get_user_meta($user->ID, 'location', true),
            'status' => get_user_meta($user->ID, 'status', true),
        ];

        return $data;
    }

    static function get_all_users() {
        $users = get_users([
            'role__in' => ['member', 'editor', 'admin'],
            'fields' => ['ID']
        ]);

        $data = [];
        foreach ($users as $user) {
            $data[] = self::get_user_data($user->ID);
        }

        return $data;
    }

    /**
     * delete user
     * @param int $id
     */
    public function on_delete_user( $id ) {
        $user = get_user_by('id', $id);
        if(!$user) {
            return;
        }

        // delete avatar
        $avatar = get_user_meta($id, 'avatar', true);
        if($avatar) {
            wp_delete_attachment($avatar, true);
        }
    }

    /**
     * delete user
     * @param int $id
     */
    static function delete_user( $id ) {
        $user = self::get_user_data($id);
        if(!$user) {
            throw new Exception('User not found');
        }

        $response = wp_delete_user($id);
        if( is_wp_error($response) ) {
            throw new Exception('Failed to delete user');
        }

        return $user;
    }
}