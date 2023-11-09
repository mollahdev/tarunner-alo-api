<?php 
namespace WP_SM_API\Model;
/**
 * Model for student
 * This trait is used in StudentController, so we can use the methods in StudentController
 * Communicate with database to save and get data
 * 
 * @package WP_SM_API\Model
 * @since 1.0.0
 * @version 1.0.0
 * @see WP_SM_API\Controller\StudentController
 */ 

trait StudentModel
{
    protected function getNamespace() {
        return 'student/v1';
    }

    private function getPostType() {
        return 'wp_sm_api_student';
    }

    /**
     * take student data and save into database
     * @return {string} 
     */ 
    protected function create($params) {
        $post = array(
            'post_status' => 'publish',
            'post_type' => $this->getPostType(),
            'meta_input' => $params
        );
    
        return wp_insert_post($post);
    }

    /**
     * get list of students 
     * @param {array} $ids
     * @return {array}
     */ 
    protected function find( $ids = [] ) {
        $data = [];
        /**
         * if $ids is empty, get all students
         * else get students by ids 
         */ 
        if (empty($ids)) {
            $posts = get_posts([
                'post_type' => $this->getPostType(),
                'post_status' => 'any',
                'posts_per_page' => -1
            ]);

            foreach ($posts as $post) {
                $data[] = $this->getMetaInfo($post->ID);
            }
        } else {
            foreach ($ids as $id) {
                $meta = get_post_meta($id);
                // if student not found, skip
                if( empty($meta) ) continue;
                $data[] = $this->getMetaInfo($id);
            }
        }

        return $data;

    }

    private function getMetaInfo( $id ) {
        $meta = get_post_meta($id);
        return [
            'id'                => $id,
            'first_name'        => $this->validateValue($meta, 'first_name'),
            'last_name'         => $this->validateValue($meta, 'last_name'),
            'gender'            => $this->validateValue($meta, 'gender'),
            'date_of_birth'     => $this->validateValue($meta, 'date_of_birth'),
            'email'             => $this->validateValue($meta, 'email'),
            'mobile'            => $this->validateValue($meta, 'mobile'),
            'country_code'      => $this->validateValue($meta, 'country_code'),
            'is_student'        => $this->validateValue($meta, 'is_student'),
            'institution_type'  => $this->validateValue($meta, 'institution_type'),
            'class'             => $this->validateValue($meta, 'class'),
            'group'             => $this->validateValue($meta, 'group'),
            'subject'           => $this->validateValue($meta, 'subject'),
            'avatar'            => wp_get_attachment_url($this->validateValue($meta, 'avatar', 0)),
            'course_id'         => $this->validateValue($meta, 'course_id'),
            'status'            => $this->validateValue($meta, 'status'),
            'is_onboarding_completed' => $this->validateValue($meta, 'is_onboarding_completed'),
        ];
    }

    private function validateValue( $meta, $key, $default = null ) {
        if( isset($meta[$key]) && !empty($meta[$key]) ) {
            return $meta[$key][0];
        } else {
            return $default;
        }
    }

    protected function deleteAll( $ids = [] ) {
        $posts = $this->find( $ids );

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

    protected function isEmailExists( $email ) {
        $posts = get_posts([
            'post_type' => $this->getPostType(),
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