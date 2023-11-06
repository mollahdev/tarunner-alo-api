<?php 
namespace WP_SM_API\App;
use Rakit\Validation\Validator;
abstract class Api {
    abstract protected function getNamespace();
    public $param = '';

    /**
     * @var \WP_REST_Request
     */
    public $request;
    protected $validator;

    public function __construct() {
        $this->validator = new Validator;

        add_action(
            'rest_api_init', function () {
                register_rest_route(
                    untrailingslashit( 'wp-sm-api/' . $this->getNamespace() ), '/(?P<action>\w+)/' . ltrim( $this->param, '/' ), [
                        'methods'             => \WP_REST_Server::ALLMETHODS,
                        'callback'            => [$this, 'action'],
                        'permission_callback' => function () {
                            $user = wp_get_current_user();
                            if ($user->ID === 0) {
                                return new \WP_Error('rest_forbidden', __('You must be logged in to access this endpoint.'), array('status' => 401));
                            }
                            return true;
                        }
                    ] 
                );
            } 
        );
    }

    /**
     * @param $request
     * @return mixed
     */
    public function action( \WP_REST_Request $request ) {
        $this->request = $request;
        $action_class  = strtolower( $this->request->get_method() ) . '_' . sanitize_key( $this->request['action'] );

        if ( method_exists( $this, $action_class ) ) {
            unset($this->request['action']);
            return $this->{$action_class}();
        }
    }
    
}