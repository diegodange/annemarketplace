<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Products{
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'woocommerce_product_options_general_product_data', [$this,'add_custom_vendor_field']);
        add_action( 'woocommerce_process_product_meta',  [$this,'save_custom_vendor_field']);
    }
        // add_role( 'vendor', 'Vendedor', array(
        //     'read'         => true,
        //     'edit_posts'   => true,
        //     'delete_posts' => true,
        // ) );

    public static function add_custom_vendor_field($post_id) {

        global $post;
        
        $vendors = get_users( array(
            'role'    => 'vendor',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ) );
        
        $options = array();
        
        foreach ( $vendors as $vendor ) {
            $options[ $vendor->ID ] = $vendor->display_name;
        }

        echo '<div class="options_group">';
        
        woocommerce_wp_select( array(
            'id'          => '_vendor_id',
            'name' => '_vendor_id[]',
            'label'       => __( 'Vendedor', 'woocommerce' ),
            'description' => __( 'Selecione o(s) Vendedor(es) para este Produto', 'woocommerce' ),
            'options' => $options,
            'fields'     => array( 'ID', 'display_name' ),
            'class'=> 'select2', 
            'custom_attributes' => array('multiple' => 'multiple'),
            'desc_tip'    => true, 

        ) );
        
        echo '</div>';
    }

    public static function save_custom_vendor_field( $post_id ) {
        if (  $_POST['_vendor_id']  ) {
            update_post_meta( $post_id, '_vendor_id', $_POST['_vendor_id'] );
        }
    }

}

Products::getInstance();