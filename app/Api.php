<?php 
namespace WP_SM_API\App;

abstract class Api {
    abstract protected function __construct();
    public $request;
    public $validator;
    public $empty_object;
    public $prefix;

    protected function route( string $method, string $route = '/', $callback = null, $permission_callback = null ) {
        add_action(
            'rest_api_init', function () use ( $method, $route, $callback, $permission_callback ) {
                register_rest_route(
                    'wp-sm-api', 
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
}