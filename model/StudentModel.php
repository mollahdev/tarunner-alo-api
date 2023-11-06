<?php 
namespace WP_SM_API\Model;

trait StudentModel
{
    protected function getNamespace() {
        return 'student/v1';
    }

    private function tableName() {
        global $wpdb;
        return $wpdb->prefix . 'students';
    }

    /**
     * take student data and save into database
     * @return {string} 
     */ 
    protected function create($params) {
        

    }
}