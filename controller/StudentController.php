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
     * delete all students
     */
    public function delete_all() {
        $posts = $this->deleteAll();
        return new WP_REST_Response($posts, 200);
    }
    /**
     * delete by id
     */
    public function delete_list() {
        $params = $this->request->get_params();

        $validation = $this->validator->validate($params + $_FILES, [
            'ids' => 'required|array',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        } 

        $posts = $this->deleteAll( $params['ids'] );
        return new WP_REST_Response($posts, 200);
    }
    /**
     * create a student
    */
    public function post_register() {
        $params = $this->request->get_params();
        $params['status'] = 'pending';
        $params['is_onboarding_completed'] = 'no';
        $params['random_token'] = rand(100000, 999999);

        $validation = $this->validator->validate($params + $_FILES, [
            'first_name'        => 'required',
            'last_name'         => 'required',
            'gender'            => 'required|in:male,female',
            'date_of_birth'     => 'required|date:d-m-Y',
            'email'             => 'required|email',
            'mobile'            => 'required|numeric|digits:10',
            'country_code'      => 'required',
            'is_student'        => 'required|in:yes,no',
            'institution_type'  => 'required|in:school,college,madrasah,university',
            'class'             => 'required|in:1,2,3,4,5,6,7,8,9,10,11,12,honours,masters,degree',
            'group'             => 'in:science,humanities,commerce,general',
            'subject'           => 'present',
            'avatar'            => 'required|uploaded_file|max:1M|mimes:jpeg,png,webp',
            'course_id'         => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new WP_REST_Response($errors->firstOfAll(), 400);
        } 

        if( $this->isEmailExists( $params['email'] ) ) {
            return new WP_REST_Response(['email' => 'Email already exists'], 400);
        }

        // upload the file in wordpress media
        
        $id = $this->create($params);
        $params['id'] = $id;
        $attachment_id = media_handle_upload('avatar', $id);
        
        if (is_wp_error($attachment_id)) {
            //delete post
            wp_delete_post($id);
            return new WP_REST_Response($attachment_id->get_error_message(), 400);
        }

        // update post meta
        update_post_meta($id, 'avatar', $attachment_id);
        
        // get image url
        $params['avatar'] = wp_get_attachment_url($attachment_id);

        // delete random_token
        unset($params['random_token']);

        return new WP_REST_Response($params, 200);
    }
}