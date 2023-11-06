<?php 
namespace WP_SM_API\Controller;
use WP_SM_API\Model\StudentModel;
use WP_SM_API\App\Api;
use WP_REST_Response;

class StudentController extends Api
{
    use StudentModel;
    /**
     * get list of students 
     */ 
    public function get_list() {
        $data = array(
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30
        );
    
        return new WP_REST_Response($data, 200);
    }
    /**
     * create a student
    */
    public function post_create() {
        $params = $this->request->get_params();

        $response = [
            'name' => $params['name'],
            'email' => $params['email'],
            'age' => $params['age']
        ];

        return new WP_REST_Response($response, 200);
    }
}