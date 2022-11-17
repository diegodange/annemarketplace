<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard{
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }


    public function __construct() {
     
    }


    public static function layout(){
        $html = <<<HTML
        <div class="wrap">
            <h1>Dashboard</h1>
        </div>
        HTML;

        echo $html;
    }


}

Dashboard::getInstance();
