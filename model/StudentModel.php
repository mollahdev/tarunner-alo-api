<?php 
namespace Tarunner_Alo\Model;
use Tarunner_Alo\App\Singleton;

class StudentModel
{
    use Singleton;

    private static function get_post_type() {
        return 'wp_sm_api_student';
    }
    /**
     * take student data and save into database
     * @return {string} 
     */ 
    static function create($params) {
        $post = array(
            'post_status' => 'publish',
            'post_type' => self::get_post_type(),
            'meta_input' => $params
        );
    
        return wp_insert_post($post);
    }
    /**
     * get list of students 
     * @param {array} $ids
     * @return {array}
     */ 
    static function find( $ids = [] ) {
        $args = array(
            'post__in' => $ids,
            'post_type' => self::get_post_type(),
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        
        $posts = get_posts($args);
        $data = [];

        foreach ($posts as $post) {
            $data[] = self::get_meta_info($post->ID);
        }
        return $data;
    }

    private static function get_meta_info( $id ) {
        $meta = get_post_meta($id);
        return [
            'id'                => $id,
            'first_name'        => self::validateValue($meta, 'first_name'),
            'last_name'         => self::validateValue($meta, 'last_name'),
            'gender'            => self::validateValue($meta, 'gender'),
            'date_of_birth'     => self::validateValue($meta, 'date_of_birth'),
            'email'             => self::validateValue($meta, 'email'),
            'mobile'            => self::validateValue($meta, 'mobile'),
            'country_code'      => self::validateValue($meta, 'country_code'),
            'is_student'        => self::validateValue($meta, 'is_student'),
            'institution_type'  => self::validateValue($meta, 'institution_type'),
            'class'             => self::validateValue($meta, 'class'),
            'group'             => self::validateValue($meta, 'group'),
            'subject'           => self::validateValue($meta, 'subject'),
            'avatar'            => wp_get_attachment_url(self::validateValue($meta, 'avatar', 0)),
            'services'           => json_decode(self::validateValue($meta, 'services'), true),
            'status'            => self::validateValue($meta, 'status'),
            'is_onboarding_completed' => self::validateValue($meta, 'is_onboarding_completed')
        ];
    }

    private static function validateValue( $meta, $key, $default = null ) {
        if( isset($meta[$key]) && !empty($meta[$key]) ) {
            return $meta[$key][0];
        } else {
            return $default;
        }
    }

    static function delete_all( $ids = [] ) {
        $posts = self::find( $ids );
        foreach ($posts as $post) {
            // get attachment id of avatar
            $attachment_id = get_post_meta($post['id'], 'avatar', true);
            // delete attachment
            wp_delete_attachment($attachment_id, true);
            // delete post
            wp_delete_post($post['id'], true);
        }

        return $posts;
    }

    static function is_email_exists( $email ) {
        $posts = get_posts([
            'post_type' => self::get_post_type(),
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'email',
                    'value' => $email,
                    'compare' => '='
                ]
            ]
        ]);

        return !empty($posts);
    }
}