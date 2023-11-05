<?php 
namespace WP_SM_API\Controller;
use WP_SM_API\Model\StudentModel;
use WP_SM_API\App\Api;
use WP_REST_Response;

class StudentController extends Api
{
    use StudentModel;
    public function get_list() {
        $data = array(
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30
        );
    
        return new WP_REST_Response($data, 200);
    }
}