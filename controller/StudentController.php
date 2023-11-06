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
        $posts = $this->find();
        return new WP_REST_Response($posts, 200);
    }
    /**
     * create a student
    */
    public function post_create() {
        $params = $this->request->get_params();
        
        $validation = $this->validator->validate($params, [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        } else {
            $id = $this->create($params);
            $params['id'] = $id;
            return new WP_REST_Response($params, 200);
        }
    }
}