<?php 
namespace WP_SM_API\Controller;
use WP_SM_API\Model\ServiceModel;
use WP_SM_API\App\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_SM_API\App\Singleton;

class ServiceController extends Api
{
    use Singleton;
    private $service_model = null;

    public function __construct()
    {
        $this->prefix = 'service';
        $this->service_model = new ServiceModel();

        parent::__construct();
    }

    function manage_routes()
    {
        // get all students
        $this->route( WP_REST_Server::READABLE, '/', 'get_services', 'access_admin');
        // create service
        $this->route( WP_REST_Server::EDITABLE, '/', 'post_create_service', 'access_admin' );
    }

    /**
     * get all students 
     * @method GET
     * @example /wp-json/wp-sm-api/$namespace
     */ 
    function get_services() {
        $services = $this->service_model->get_all_services();
        return new WP_REST_Response($services, 200);
    }
    /**
     * create service
     * @method POST
     * @example /wp-json/wp-sm-api/$namespace
     */ 
    public function post_create_service() {
        $params = $this->request->get_params();
        $validation = $this->validator->validate($params + $_FILES, [
            'name'          => 'required',
            "description"   => "present",
            "category_id"   => "required|numeric",
            "thumbnail"     => 'required|uploaded_file|max:1M|mimes:jpeg,png,webp',
            "admission_fee" => "required|numeric",
            'payment_type'  => 'required|in:monthly,single,partial',
            'monthly_fee'   => 'required_if:payment_type,monthly|numeric',
            'single_fee'    => 'required_if:payment_type,single|numeric',
            'partial_fee'   => 'required_if:payment_type,partial',
            'status'        => 'required|in:active,inactive',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        }

        try {
            $id = $this->service_model->create_service($params);
            $data = $this->service_model->get_service_by_id($id);
            return new WP_REST_Response($data, 200);
        } catch (\Throwable $th) {
            return new WP_REST_Response($th->getMessage(), 400);
        }
    }
}