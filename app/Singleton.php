<?php
namespace Tarunner_Alo\App;

trait Singleton
{
    /**
     * @var mixed
     */
    private static $instance;

    /**
     * @return static
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}