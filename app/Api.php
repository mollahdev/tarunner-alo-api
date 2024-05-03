<?php 
namespace Tarunner_Alo\App;

use Tarunner_Alo\App\Constants;
use Rakit\Validation\Validator;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use stdClass;

abstract class Api {
    public $request;
    public $validator;
    public $empty_object;
    public $prefix;

    protected function __construct()
    {
        $this->validator = new Validator;
        $this->empty_object = new stdClass();
        $this->manage_routes();
    }

    abstract function manage_routes();

    protected function route( string $method, string $route = '/', $callback = null, $permission_callback = null ) {
        add_action(
            'rest_api_init', function () use ( $method, $route, $callback, $permission_callback ) {
                register_rest_route(
                    'tarunner-alo-api', 
                    $this->prefix . $route, 
                    [
                        'methods'             => $method,
                        'callback'            => function ( $request ) use ( $callback ) {
                            $this->request = $request;
                            return $this->$callback();
                        },
                        'permission_callback' => $permission_callback == null ? '__return_true' : [$this, $permission_callback],
                    ]
                );
            } 
        );
    }

    public function wp_get_current_user() {
        $user = wp_get_current_user();
        if ($user->ID === 0) {
            return new \WP_Error('rest_forbidden', __('You must be logged in to access this endpoint.'), array('status' => 401));
        }
        return true;
    }

    private function extract_token( $request ) {
        $headers = $request->get_headers();

        // check if authorization header exists
        if( !isset($headers['authorization']) ) {
            return new \WP_Error('rest_forbidden', __('Access Token does not exists'), array('status' => 401));
        }

        try {
            $auth = $headers['authorization'][0];
            $token = str_replace('Bearer ', '', $auth);
            $decoded = JWT::decode($token, new Key(Constants::$JWT_KEY, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return new \WP_Error('rest_forbidden', __('You must be logged in as admin to access this endpoint.'), array('status' => 401));
        }
    }

    private function is_token_valid( $data, $request ) {
        $expOk = $data->exp > time();
        $issOk = $data->iss == $request->get_header('host');
        $userExists = get_user_by('id', $data->user_id);
        return $expOk && $issOk && $userExists;
    }

    private function is_email_verified( $id ) {
        $meta = get_user_meta($id, 'is_email_verified', true);
        return $meta == 'yes';
    }

    public function access_admin( $request ) {
        $data = $this->extract_token( $request );
        if( is_wp_error($data) ) {
            return $data;
        }

        $roleOk = $data->role == 'administrator' || $data->role == 'admin';
        if( !$this->is_email_verified( $data->user_id ) ) {
            return new \WP_Error('rest_forbidden', __('You must verify your email to access this endpoint.'), array('status' => 401));
        }

        return $roleOk && $this->is_token_valid( $data, $request );
    }
}