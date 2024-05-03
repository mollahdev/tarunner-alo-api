<?php 
namespace Tarunner_Alo\Controller;
use Tarunner_Alo\Model\StudentModel;
use Rakit\Validation\Validator;
use Tarunner_Alo\App\Api;
use WP_REST_Response;
use WP_REST_Server;
use Tarunner_Alo\App\Singleton;
use stdClass;

class StudentController extends Api
{
    use Singleton;
    public function __construct()
    {
        $this->prefix = 'student';
    }

    function manage_routes()
    {
         // get all students
         $this->route( WP_REST_Server::READABLE, '/', 'get_students', 'wp_get_current_user' );
         // register a student
         $this->route( WP_REST_Server::EDITABLE, '/', 'post_register', 'wp_get_current_user' );
         // delete all students
         $this->route( WP_REST_Server::DELETABLE, '/', 'delete_students', 'wp_get_current_user' );
         // get individual student details by id
         $this->route( WP_REST_Server::READABLE, '/(?P<id>\w+)/', 'get_student', 'wp_get_current_user' );
         // delete students by ids
         $this->route( WP_REST_Server::EDITABLE, '/delete', 'post_delete_student', 'wp_get_current_user' );
    }

    /**
     * get all students 
     * @method GET
     * @example /wp-json/tarunner-alo-api/$namespace
     */ 
    public function get_students() {
        $posts = StudentModel::find();
        return new WP_REST_Response( $posts, 200);
    }
    /**
     * get student details by id
     * @method GET
     * @param {int} $id
     * @example /wp-json/tarunner-alo-api/$namespace/1 
     */ 
    public function get_student() {
        $params = $this->request->get_params();
        $validation = $this->validator->validate($params, [
            'id' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        }

        $posts = StudentModel::find( [$params['id']] );
        return new WP_REST_Response(empty($posts) ? $this->empty_object : $posts[0], 200);
    }
    /**
     * delete all students
     * @method DELETE
     * @example /wp-json/tarunner-alo-api/$namespace
     */
    public function delete_students() {
        $posts = StudentModel::delete_all();
        return new WP_REST_Response($posts , 200);
    }
    /**
     * delete students by ids
     * @method POST
     * @param {array} $ids
     * @example /wp-json/tarunner-alo-api/$namespace/delete
     */
    public function post_delete_student() {
        $params = $this->request->get_params();
        $validation = $this->validator->validate($params, [
            'ids' => 'required',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        }

        //form data coma separated string to array
        $ids = explode(',', $params['ids']);

        if( !is_array($ids) ) {
            return new WP_REST_Response(['ids' => 'ids must be an array'], 400);
        }

        $posts = StudentModel::delete_all( $ids );
        return new WP_REST_Response($posts , 200);
    }

    /**
     * register a student
     * @method POST
     * @example /wp-json/tarunner-alo-api/$namespace/register
    */
    public function post_register() {
        $params = $this->request->get_params();
        $params['status'] = 'pending';
        $params['is_onboarding_completed'] = 'no';

        $validation = $this->validator->validate($params, [
            'first_name'        => 'required',
            'last_name'         => 'required',
            'email'             => 'required|email',
            'mobile'            => 'required|numeric|digits:10',
            'country_code'      => 'required',
            'service_id'         => 'required|numeric',
            'payment_id'        => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        } 

        if( StudentModel::is_email_exists( $params['email'] ) ) {
            return new WP_REST_Response(['email' => 'Email already exists'], 400);
        }

        $service_id = $params['service_id'];

        // group course details
        $params['services'] = json_encode([
            $service_id => [
                'service_id'    => $service_id,
                'payment_id'    => $params['payment_id'],
                'batch_id'      => null,
                'status'        => 'pending',
                'created_at'    => date('d-m-Y'),
            ]
        ]);

        unset($params['service_id']);
        unset($params['payment_id']);

        $id = StudentModel::create($params);
        $params['id'] = $id;
        $params['services'] = json_decode($params['services'], true);
        return new WP_REST_Response($params, 200);
    }
}