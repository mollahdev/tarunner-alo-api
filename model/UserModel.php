<?php 
namespace WP_SM_API\Model;

use WP_SM_API\App\Singleton;
use WP_SM_API\App\Helper;
use WP_Error;

class UserModel extends BaseModel
{
    use Singleton;
    function get_post_type() {
        return 'wp_sm_api_user';
    }
    /**
     * create user
     * @param array $params 
     */ 
    function create_user( $params = [] ) {
        $role = $params['role'];
        // return wp error if role does not exists
        if( !get_role( $role ) ) {
            return new WP_Error( 'role', 'Role does not exists' );
        }
        // return wp error if email already exists
        if( email_exists( $params['email'] ) ) {
            return new WP_Error( 'email', 'Email already exists' );
        }
        //ToDo: check if student id exists when role is student
        $params['student_id'] = null;
        //toDo: check if teacher id exists when role is teacher
        $params['teacher_id'] = null;
        // generate username
        $params['username'] = Helper::generate_username($params['first_name'], $params['last_name']);

        // insert user
        $id = wp_insert_user([
            'first_name'    => $params['first_name'],
            'last_name'     => $params['last_name'],
            'user_email'    => $params['email'],
            'user_pass'     => $params['password'],
            'role'          => $params['role'],
            'user_login'    => $params['username'],
            'user_activation_key' => 'first_time',
            'meta_input'    => [
                'student_id'    => $params['student_id'],
                'teacher_id'    => $params['teacher_id'],
                'gender'        => $params['gender'],
                'phone'         => $params['phone'],
                'country_code'  => $params['country_code'],
                'date_of_birth' => $params['date_of_birth'],
                'avatar'        => null,
            ] 
        ]);

        if( is_wp_error($id) ) {
            return new WP_Error( 'user', $id->get_error_message() );
        }

        // upload avatar
        $attachment_id = media_handle_upload('avatar', $id);
        if (is_wp_error($attachment_id)) {
            // delete user
            wp_delete_user($id);
            return new WP_Error( 'avatar', $attachment_id->get_error_message() );
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
            'student' => 'Student',
            'teacher' => 'Teacher',
            'guardian' => 'Guardian',
            'admin' => 'Admin',
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
            'email' => $user->user_email,
            'username' => $user->user_login,
            'role' => $user->roles[0],
            'student_id' => get_user_meta($user->ID, 'student_id', true),
            'teacher_id' => get_user_meta($user->ID, 'teacher_id', true),
            'gender' => get_user_meta($user->ID, 'gender', true),
            'phone' => get_user_meta($user->ID, 'phone', true),
            'country_code' => get_user_meta($user->ID, 'country_code', true),
            'date_of_birth' => get_user_meta($user->ID, 'date_of_birth', true),
            'avatar' => wp_get_attachment_url(get_user_meta($user->ID, 'avatar', true)),
        ];

        return $data;
    }

    /**
     * prepare response
     * @param object $post
     * @return array 
     */ 
    private function prepare_response( $post ) {
        // convert post to array
        $post = (array) $post;
        $data = [];
        $data['status'] = $this->get_value( $post, 'status' );
        $data['name'] = $this->get_value( $post, 'name' );
        $data['description'] = $this->get_value( $post, 'description' );
        $data['category_id'] = $this->get_value( $post, 'category_id' );
        $data['thumbnail'] = wp_get_attachment_url( $this->get_value( $post, 'thumbnail', 0 ));
        $data['admission_fee'] = $this->get_value( $post, 'admission_fee' );
        $data['payment_type'] = $this->get_value( $post, 'payment_type' );
        $data['monthly_fee'] = $this->get_value( $post, 'monthly_fee' );
        $data['single_fee'] = $this->get_value( $post, 'single_fee' );
        $data['partial_fee'] = $this->get_value( $post, 'partial_fee' );
        $data['created_at'] = $this->get_value( $post, 'created_at' );
        return $data;
    }
}