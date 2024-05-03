<?php 
namespace Tarunner_Alo\Model;

use WP_Error;
use Tarunner_Alo\App\Singleton;

class ServiceModel extends BaseModel
{
    use Singleton;
    function get_post_type() {
        return 'wp_sm_api_service';
    }

    /**
     * create service 
     * @param array $params
     * @return array|WP_Error
     */ 
    function create_service( $params ) {
        $id = $this->insert_post( $params );
        $attachment_id = media_handle_upload('thumbnail', $id);

        if (is_wp_error($attachment_id)) {
            wp_delete_post($id);
            return new WP_Error('upload', $attachment_id->get_error_message(), array('status' => 400));
        }

        update_post_meta($id, 'thumbnail', $attachment_id);
        return $params;
    }

    /**
     * get all services
     * @return array 
     */ 
    function get_all_services() {
        $posts = $this->get_posts();
        $data = [];
        foreach( $posts as $post ) {
            $meta['id'] = $post->ID;
            $meta = get_post_meta( $post->ID );
            $data[] = $this->prepare_response( $meta );
        }
        return $data;
    }

    /**
     * get service by id
     * @param int $id
     * @return array|WP_Error
     */
    function get_service_by_id( $id ) {
        $post = get_post( $id );
        if( $post == null ) {
            return new WP_Error('not_found', __('Service not found'), array('status' => 404));
        }
        return $this->prepare_response( $post );
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