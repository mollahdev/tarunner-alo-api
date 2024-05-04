<?php 
namespace Tarunner_Alo\Controller;
use Tarunner_Alo\Model\UserModel;
use Tarunner_Alo\App\Api;
use WP_REST_Response;
use WP_REST_Server;
use Tarunner_Alo\App\Singleton;
use Tarunner_Alo\App\Constants;
use Firebase\JWT\JWT;
use Tarunner_Alo\Providers\Email\Mail;
use Tarunner_Alo\Providers\Email\Template;

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
        // get user list
        $this->route( WP_REST_Server::READABLE, '/list', 'get_list' );
        // delete user by id
        $this->route( WP_REST_Server::DELETABLE, '/delete/(?P<id>\d+)', 'delete_user' );
    }

    /**
     * create user
     * @method POST
     * @example /wp-json/tarunner-alo-api/$prefix/register
     */ 
    public function post_create() {
        $params = $this->request->get_params();
        $host = $this->request->get_header('host');
        $validation = $this->validator->validate($params + $_FILES, [
            "role"          => "required|in:member,editor,admin",
            "avatar"        => "required|uploaded_file|max:1M|mimes:jpeg,png,webp",
            "first_name"    => "required",
            "last_name"     => "required",
            "password"      => "required",
            "email"         => "required|email",
            "confirm_password"  => "required|same:password",
            "phone"             => "required|numeric|digits:10",
            "country_code"      => "required|numeric|digits:3",
            "date_of_birth"     => "required|date:d-m-Y",
            "plan_id"           => "required|numeric",
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response([
                'message' => 'Failed to create user',
                'errors' => $errors->firstOfAll()
            ], 400);
        }

        try {
            $response = $this->user_model->create_user($params);
            $this->mail->send($response['email'], Template::account_creation_verification( $response['id'] ));
            $response = UserModel::get_user_data($response['id']);

            $date = new \DateTimeImmutable();
            // generate access JWT token
            $payload = [
                'iat' => $date->getTimestamp(),
                'iss' => $host,
                'exp' => $date->modify('+1 day')->getTimestamp(),
                'nbf' => $date->getTimestamp(),
                'user_id' => $response['id'],
                'role' => $response['role'],
            ];
            
            // assign token
            $response['access_token'] = JWT::encode($payload, Constants::$JWT_KEY, Constants::$JWT_ALGO);

            return new WP_REST_Response( [
                'message' => 'Registration successfully',
                'data' => $response
            ], 200);

        } catch (\Exception $e) {
            return new WP_REST_Response([
                'message' => $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * verify email 
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
                'errors' => []
            ], 400);
        }

        if( $user->user_activation_key != $params['code'] ) {
            return new WP_REST_Response([
                'message' => 'Failed to verify email',
                'errors' => []
            ], 400);
        }

        // update the user status
        update_user_meta($user->ID, 'is_email_verified', 'yes');

        return new WP_REST_Response( [
            'message' => 'Email verified successfully',
            'data' => []
        ] , 200);
    }
     /**
     * create user
     * @method POST
     * @example /wp-json/tarunner-alo-api/$prefix/login
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
                    'email' => 'Invalid email or username',
                ]
            ], 400);
        }


        if( !wp_check_password($params['password'], $user->data->user_pass, $user->ID) ) {
            return new WP_REST_Response([
                'message' => 'Failed to login',
                'errors' => [
                    'password' => 'Invalid password',
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
        
        // assign token
        $data['access_token'] = JWT::encode($payload, Constants::$JWT_KEY, Constants::$JWT_ALGO);
        return new WP_REST_Response( [
            'message' => 'Logged in successfully',
            'data' => $data
        ], 200);
    }

    public function get_list() {
        $users = UserModel::get_all_users();
        return new WP_REST_Response( [
            'message' => 'User list',
            'data' => $users
        ], 200);
    }

    public function delete_user() {
        $params = $this->request->get_params();
        $validation = $this->validator->validate($params, [
            'id' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        }

        $id = $params['id'];
        
        try {
            $user = UserModel::delete_user($id);
            return new WP_REST_Response( [
                'message' => 'User deleted successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'message' => $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }
}