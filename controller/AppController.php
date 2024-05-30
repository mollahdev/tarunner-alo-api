<?php 
namespace Tarunner_Alo\Controller;
use Tarunner_Alo\App\Api;
use WP_REST_Response;
use WP_REST_Server;
use Tarunner_Alo\App\Singleton;

class AppController extends Api
{
    use Singleton;

    public function __construct()
    {
        $this->prefix = 'app';
        parent::__construct();
    }

    function manage_routes()
    {
        // get user list
        $this->route( WP_REST_Server::READABLE, '/meta', 'get_meta' );
    }

    /**
     * get app meta data
     * @method GET
     * @example /wp-json/tarunner-alo-api/$prefix/meta
     */ 
    public function get_meta() {
        return new WP_REST_Response( [
            'message' => 'App Meta Data',
            'data' => [
                'version' => '1.0.0',
                'download_link' => 'https://mollah.dev',
            ]
        ], 200);
    }
}