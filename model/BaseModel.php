<?php 
namespace Tarunner_Alo\Model;

abstract class BaseModel {
    abstract function get_post_type();
    /**
     * take student data and save into database
     * @return {int} 
     */ 
    protected function insert_post($params) {
        $post = array(
            'post_status' => 'publish',
            'post_type' =>$this->get_post_type(),
            'meta_input' => $params
        );
    
        return wp_insert_post($post);
    }
    /**
     * get all post list from database
     * @param {array} $ids
     * @return {array}
     */ 
    protected function get_posts( $ids = [] ) {
        $args = array(
            'post__in' => $ids,
            'post_type' => $this->get_post_type(),
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        
        return get_posts($args);
    }
    /**
     * remove all from database
     * @param {array} $ids
     * @return {array}
     */ 
    protected function delete_posts( $ids = [], $attachment_keys = [], $before_delete = null ) {
        $posts = self::get_posts( $ids );
        foreach ($posts as $post) {
            // delete attachments
            foreach ($attachment_keys as $key) {
                $attachment_id = get_post_meta($post['id'], $key, true);
                wp_delete_attachment($attachment_id, true);
            }

            if( $before_delete != null ) {
                $before_delete( $post );
            }

            wp_delete_post($post['id'], true);
        }

        return $posts;
    }

    /**
     * get value from meta data 
     * @param {array} $meta
     * @param {string} $key
     * @param {any} $default
     * @return {any}
     */ 
    protected function get_value( $meta, $key, $default = null ) {
        if( isset($meta[$key]) && !empty($meta[$key]) ) {
            return $meta[$key][0];
        } else {
            return $default;
        }
    }
}