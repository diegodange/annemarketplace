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

        add_role( 'vendor', 'Vendedor', array(
            'read'         => true,
            'edit_posts'   => true,
            'delete_posts' => true,
        ) );

        add_action( 'init', [$this,'add_vendor_capabilities'] );
        add_action( 'pre_get_posts', [$this,'filter_products_by_vendor'] );

    }


    public static function filter_products_by_vendor( $query ) {
    // Verifica se o usuário atual é um vendedor
    if ( current_user_can( 'vendor' ) ) {
        // Obtém o ID do usuário atual
        $user_id = get_current_user_id();
        
        // Adiciona uma cláusula para filtrar por autor (ID do usuário)
        $query->set( 'author', $user_id );
    }
    }


    public static function add_vendor_capabilities() {
        $role = get_role( 'vendor' );
        $role->add_cap( 'edit_products' );
        $role->add_cap( 'edit_others_products' );
        $role->add_cap( 'publish_products' );
        $role->add_cap( 'read_products' );
        $role->add_cap( 'read_private_products' );
        $role->add_cap( 'delete_products' );
        $role->add_cap( 'delete_private_products' );
        $role->add_cap( 'delete_published_products' );
        $role->add_cap( 'delete_others_products' );
    }

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
            // Obtém o ID do usuário do tipo "vendor" para o produto
            $vendor_id = get_post_meta($post_id, '_vendor_id', true);
            // Obtém uma lista de objetos WP_User
            $user_query = new WP_User_Query( array( 'role' => 'vendor' ) );
            $users = $user_query->get_results();
            $names = array();
            for ($i=0; $i < count($vendor_id) ; $i++) { 
                // Itera sobre a lista e imprime o valor de user_login de cada objeto
                foreach ( $users as $user ) {
                    if ($vendor_id[$i] == $user->data->ID) {
                        $first_name = get_user_meta( $user->data->ID, 'first_name', true );
                        $last_name = get_user_meta( $user->data->ID, 'last_name', true );
                        $complete_name = $first_name.' '.$last_name;
                        $profile_url = get_edit_user_link( $user->data->ID );
                        $names[] = '<a href="' . $profile_url . '">' . $complete_name . '</a>';                        
                    }
                }
            }
            echo implode(', ', $names);
        }
    }  


}

Products::getInstance();