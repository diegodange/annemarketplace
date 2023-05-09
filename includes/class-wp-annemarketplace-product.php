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

        add_action( 'restrict_manage_posts', [$this,'add_vendor_filter']);
        add_action( 'pre_get_posts', [$this,'filter_products_by_vendor']);

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

    // Adiciona uma caixa de pesquisa para o campo "Vendedor"
    public static function add_vendor_filter() {
        global $wpdb, $wp_query;
        $vendor_id = isset( $_GET['vendor'] ) ? $_GET['vendor'] : '';
        echo '<select name="vendor">';
        echo '<option value="">' . __( 'Todos os Vendedores', 'textdomain' ) . '</option>';
        $users = $wpdb->get_results( "SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%vendor%'" );
        foreach ( $users as $user ) {
            $user_info = get_userdata( $user->user_id );
            $selected = selected( $user->user_id, $vendor_id, false );
            echo '<option value="' . esc_attr( $user->user_id ) . '" ' . $selected . '>' . esc_html( $user_info->user_nicename ) . '</option>';
        }
        echo '</select>';
    }

    // Filtra a consulta com base no nome do vendedor
    public static function filter_products_by_vendor( $query ) {
        global $pagenow;
        $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';
        if ( $post_type == 'product' && is_admin() && $pagenow == 'edit.php' && isset( $_GET['vendor'] ) && $_GET['vendor'] != '' ) {
            $vendor_id = $_GET['vendor'];
            $vendor_name = get_user_meta( $vendor_id, 'first_name', true ) . ' ' . get_user_meta( $vendor_id, 'last_name', true );
            $meta_query = array(
                array(
                    'key' => '_vendor_id',
                    'value' => $vendor_id,
                    'compare' => '=',
                ),
            );
            var_dump($meta_query);
            $query->set( 'meta_query', $meta_query );
            $query->set( 'meta_key', '_vendor_id' );
            $query->set( 'orderby', 'meta_value' );
            $query->set( 'order', 'ASC' );
            $query->set( 's', $vendor_name ); // Adiciona o nome do vendedor como termo de pesquisa para o campo "s"
        }
    }
    
    

}

Products::getInstance();