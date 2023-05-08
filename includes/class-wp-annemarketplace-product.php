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

        add_filter( 'manage_product_posts_columns', [$this,'my_custom_products_table_column'], 20, 1 );
        add_action( 'manage_product_posts_custom_column', [$this,'my_custom_products_table_column_content'], 20, 2 );

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
    
    // Adiciona uma nova coluna "Vendedor" à tabela de listagem de produtos
    public static function my_custom_products_table_column( $columns ) {
        $new_columns = array();
        foreach( $columns as $column_key => $column_value ) {
            $new_columns[ $column_key ] = $column_value;
            if ( 'product_tag' === $column_key ) { // Adiciona após a coluna "Tag"
                $new_columns['product_vendor'] = __( 'Vendedor', 'woocommerce' );
            }
        }
        return $new_columns;
    }

    // Exibe o nome do vendedor na coluna "Vendedor" da tabela de listagem de produtos
    public static function my_custom_products_table_column_content( $column, $post_id ) {
        if ( 'product_vendor' === $column ) {
            $vendor_id = get_post_meta( $post_id, '_vendor_id', true );
            if ( ! empty( $vendor_id ) ) {
                $vendor = get_user_by( 'id', $vendor_id );
                echo esc_html( $vendor->display_name );
            } else {
                echo '-';
            }
        }
    }


}

Products::getInstance();