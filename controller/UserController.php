<?php 
namespace WP_SM_API\Controller;
use WP_SM_API\Model\UserModel;
use WP_SM_API\App\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_SM_API\App\Singleton;
use WP_SM_API\App\Constants;
use Firebase\JWT\JWT;
use WP_SM_API\Providers\Email\Mail;
use WP_SM_API\Providers\Email\Template;

class UserController extends Api
{
    use Singleton;
    private $user_model = null;
    private $mail = null;

    public function __construct()
    {
        $this->prefix = 'user';
        $this->user_model = new UserModel();
        $this->mail = new Mail();

        parent::__construct();
    }

    function manage_routes()
    {
        // create user
        $this->route( WP_REST_Server::EDITABLE, '/register', 'post_create' );
        // verify user email
        $this->route( WP_REST_Server::EDITABLE, '/verify/email', 'post_verify_email' );
        // login user
        $this->route( WP_REST_Server::EDITABLE, '/login', 'post_login' );
    }

    /**
     * create user
     * @method POST
     * @example /wp-json/wp-sm-api/$prefix/register
     */ 
    public function post_create() {
        $params = $this->request->get_params();
        $validation = $this->validator->validate($params + $_FILES, [
            "role" => "required|in:admin,teacher,student,guardian",
            "student_id" => "required_if:role,student",
            "teacher_id" => "required_if:role,teacher",
            "avatar" => "required_if:role,admin,guardian|uploaded_file|max:1M|mimes:jpeg,png,webp",
            "first_name" => "required_if:role,admin,guardian",
            "last_name" => "required_if:role,admin,guardian",
            "email"=> "required_if:role,admin,guardian",
            "password"=> "required",
            "confirm_password"=> "required|same:password",
            "gender"=> "required_if:role,admin,guardian|in:male,female",
            "phone"=> "required_if:role,admin,guardian|numeric|digits:10",
            "country_code" => "required_if:role,admin,guardian",
            "date_of_birth"=> "required_if:role,admin,guardian|date:d-m-Y",
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response([
                'message' => 'Failed to create user',
                'errors' => $errors->firstOfAll()
            ], 400);
        }

        try {
            $id = $this->user_model->create_user($params);


            return new WP_REST_Response( $id, 200);
        } catch (\Exception $e) {
            return new WP_REST_Response('failed to create user', 400);
        }
    }

    /**
     * verify email address 
     * @method GET
     */ 
    public function post_verify_email() {
        $params = $this->request->get_params();
        $validation = $this->validator->validate($params, [
            "user_id" => "required|numeric",
            "code" => "required|numeric|digits:6",
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response([
                'message' => 'Failed to verify email',
                'errors' => $errors->firstOfAll()
            ], 400);
        }

        $user = get_user_by('id', $params['user_id']);
        if( !$user ) {
            return new WP_REST_Response([
                'message' => 'Failed to verify email',
                'errors' => [
                    'user_id' => 'User does not exists'
                ]
            ], 400);
        }

        if( $user->user_activation_key != $params['code'] ) {
            return new WP_REST_Response([
                'message' => 'Failed to verify email',
                'errors' => [
                    'code' => 'Code is incorrect'
                ]
            ], 400);
        }

        return new WP_REST_Response( 'Successfully verified email' , 200);
    }
     /**
     * create user
     * @method POST
     * @example /wp-json/wp-sm-api/$prefix/login
     */ 
    public function post_login() {
        $params = $this->request->get_params();
        $host = $this->request->get_header('host');
        
        $validation = $this->validator->validate($params, [
            "email"=> "required_without:username|email",
            "username"=> "required_without:email",
            "password"=> "required",
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response([
                'message' => 'Failed to login',
                'errors' => $errors->firstOfAll()
            ], 400);
        }

        $email_or_username = $params['email'] ?? $params['username'];
        $user = null;

        if( is_email($email_or_username) ) {
            $user = get_user_by('email', $email_or_username);
        } else {
            $user = get_user_by('login', $email_or_username);
        }

        if( !$user ) {
            return new WP_REST_Response([
                'message' => 'Failed to login',
                'errors' => [
                    'email' => 'Email or username does not exists'
                ]
            ], 400);
        }

        if( !wp_check_password($params['password'], $user->data->user_pass, $user->ID) ) {
            return new WP_REST_Response([
                'message' => 'Failed to login',
                'errors' => [
                    'password' => 'Password is incorrect'
                ]
            ], 400);
        }

        $data = UserModel::get_user_data($user->ID);
        $date = new \DateTimeImmutable();
        // generate access JWT token
        $payload = [
            'iat' => $date->getTimestamp(),
            'iss' => $host,
            'exp' => $date->modify('+1 day')->getTimestamp(),
            'nbf' => $date->getTimestamp(),
            'user_id' => $user->ID,
            'role' => $data['role'],
        ];
        // send verification email
        $this->mail->send($user->data->user_email, Template::account_creation_verification( $user->ID ) );
        // assign token
        $data['access_token'] = JWT::encode($payload, Constants::$JWT_KEY, Constants::$JWT_ALGO);
        return new WP_REST_Response( $data, 200);
    }
}