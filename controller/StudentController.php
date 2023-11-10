<?php 
namespace WP_SM_API\Controller;
use WP_SM_API\Model\StudentModel;
use Rakit\Validation\Validator;
use WP_SM_API\App\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_SM_API\App\Singleton;
use stdClass;

class StudentController extends Api
{
    use Singleton;
    public function __construct()
    {
        $this->validator = new Validator;
        $this->empty_object = new stdClass();
        $this->prefix = 'student';
        // get all students
        $this->route( WP_REST_Server::READABLE, '/', 'all_student_list', 'wp_get_current_user' );
        // delete all students
        $this->route( WP_REST_Server::DELETABLE, '/', 'delete_all_students', 'wp_get_current_user' );
        // get individual student details by id
        $this->route( WP_REST_Server::READABLE, '/(?P<id>\w+)/', 'get_student_by_id', 'wp_get_current_user' );
        // delete students by ids
        $this->route( WP_REST_Server::EDITABLE, '/delete', 'delete_studets_by_ids', 'wp_get_current_user' );
        // register a student
        $this->route( WP_REST_Server::EDITABLE, '/register', 'register_student', 'wp_get_current_user' );
    }
    /**
     * get all students 
     * @method GET
     * @example /wp-json/wp-sm-api/$namespace
     */ 
    public function all_student_list() {
        $posts = StudentModel::find();
        return new WP_REST_Response( $posts, 200);
    }
    /**
     * get student details by id
     * @method GET
     * @param {int} $id
     * @example /wp-json/wp-sm-api/$namespace/1 
     */ 
    public function get_student_by_id() {
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
     * @example /wp-json/wp-sm-api/$namespace
     */
    public function delete_all_students() {
        $posts = StudentModel::delete_all();
        return new WP_REST_Response($posts , 200);
    }
    /**
     * delete students by ids
     * @method POST
     * @param {array} $ids
     * @example /wp-json/wp-sm-api/$namespace/delete
     */
    public function delete_studets_by_ids() {
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
     * @example /wp-json/wp-sm-api/$namespace/register
    */
    public function register_student() {
        $params = $this->request->get_params();
        $params['status'] = 'pending';
        $params['is_onboarding_completed'] = 'no';

        $validation = $this->validator->validate($params, [
            'first_name'        => 'required',
            'last_name'         => 'required',
            'email'             => 'required|email',
            'mobile'            => 'required|numeric|digits:10',
            'country_code'      => 'required',
            'course_id'         => 'required|numeric',
            'payment_id'        => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        } 

        if( StudentModel::is_email_exists( $params['email'] ) ) {
            return new WP_REST_Response(['email' => 'Email already exists'], 400);
        }

        $course_id = $params['course_id'];

        // group course details
        $params['courses'] = json_encode([
            $course_id => [
                'course_id'     => $course_id,
                'payment_id'    => $params['payment_id'],
                'batch_id'      => null,
                'status'        => 'pending',
                'created_at'    => date('d-m-Y'),
            ]
        ]);

        unset($params['course_id']);
        unset($params['payment_id']);

        $id = StudentModel::create($params);
        $params['id'] = $id;
        $params['courses'] = json_decode($params['courses'], true);
        return new WP_REST_Response($params, 200);
    }
}