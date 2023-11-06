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
            ]);

            foreach ($posts as $post) {
                $meta = get_post_meta($post->ID);
                $data[] = [
                    'id' => $post->ID,
                    'name' => $meta['name'][0],
                    'email' => $meta['email'][0],
                    'age' => $meta['age'][0],
                ];
            }
        } else {
            foreach ($ids as $id) {
                $meta = get_post_meta($id);
                // if student not found, skip
                if( empty($meta) ) continue;
                $data[] = [
                    'id' => $id,
                    'name' => $meta['name'][0],
                    'email' => $meta['email'][0],
                    'age' => $meta['age'][0],
                ];
            }
        }

        return $data;

    }
}