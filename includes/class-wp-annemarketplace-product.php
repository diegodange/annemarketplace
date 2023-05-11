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

        add_action( 'init', [$this,'add_vendor_product_caps'] );
        
        add_filter( 'views_edit-product', [$this,'custom_product_subsubsub']);

        add_action( 'admin_menu', [$this, 'remove_menu_items_for_vendor'], 999 );

        add_filter( 'woocommerce_register_post_type_shop_order', function( $args ) {
            $args['capabilities'] = array(
                'read_private_shop_orders' => 'read_private_shop_orders',
                'edit_shop_orders' => 'edit_shop_orders',
                'edit_others_shop_orders' => 'edit_others_shop_orders',
                'edit_published_shop_orders' => 'edit_published_shop_orders',
                'publish_shop_orders' => 'publish_shop_orders',
                'read_shop_order' => 'read_shop_order',
                'delete_shop_orders' => 'delete_shop_orders',
                'delete_private_shop_orders' => 'delete_private_shop_orders',
                'delete_published_shop_orders' => 'delete_published_shop_orders',
                'delete_others_shop_orders' => 'delete_others_shop_orders',
            );
            return $args;
        });
    
        add_action('woocommerce_process_product_meta', [$this, 'salvar_vendor_id_produto']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'exibir_vendor_id_pedido'], 10, 1);
        add_action('woocommerce_checkout_create_order', [$this, 'vincular_vendor_a_pedido'], 10, 1);
        add_filter( 'ajax_query_attachments_args', [$this,'filter_vendor_media_library']);
        add_action( 'pre_get_posts', [$this,'filter_vendor_content']);
        add_filter( 'posts_where', [$this,'filter_vendor_media_library_list_mode'], 10, 2 );
        add_filter('views_edit-shop_order',[$this,'custom_vendor_order_subsubsub']);

    }

    // Personaliza as contagens na listagem de pedidos
    function custom_vendor_order_subsubsub($views) {
        // Verifica se o usuário atual é um vendedor
        if (current_user_can('vendor')) {
            $user_id = get_current_user_id();

            // Obtém o total de pedidos do vendedor
            $args = array(
                'post_type'      => 'shop_order',
                'posts_per_page' => -1,
                // 'author'         => $user_id,
                'post_status'    => array('wc-processing', 'wc-completed', 'wc-on-hold'),
                'meta_query'     => array(
                    array(
                        'key'     => '_vendor_id',
                        'value'   => $user_id,
                        'compare' => '=',
                    ),
                ),
            );

            $total_orders = get_posts($args);
            $processing_count = 0;
            $completed_count = 0;
            $on_hold_count = 0;

            foreach ($total_orders as $order_id) {
                $order = wc_get_order($order_id);
                $status = $order->get_status();

                switch ($status) {
                    case 'processing':
                        $processing_count++;
                        break;
                    case 'completed':
                        $completed_count++;
                        break;
                    case 'on-hold':
                        $on_hold_count++;
                        break;
                }
            }

            // Remove as contagens originais
            unset($views['all']);
            unset($views['wc-processing']);
            unset($views['wc-completed']);
            unset($views['wc-on-hold']);

            // Adiciona as novas contagens
            $views['wc-processing'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=processing&author=' . $user_id),
                'processing' === get_query_var('post_status') ? ' class="current"' : '',
                __('Processing'),
                $processing_count
            );

            $views['wc-completed'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=completed&author=' . $user_id),
                'completed' === get_query_var('post_status') ? ' class="current"' : '',
                __('Completed'),
                $completed_count
            );

            $views['wc-on-hold'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=on-hold&author=' . $user_id),
                'on-hold' === get_query_var('post_status') ? ' class="current"' : '',
                __('On Hold'),
                $on_hold_count
            );
        }

        return $views;
    }


    function filter_vendor_content( $query ) {
        // Verifica se o usuário atual tem o papel 'vendor'
        if ( in_array( 'vendor', wp_get_current_user()->roles ) && is_admin() && $query->is_main_query() ) {
            // Obtém o ID do usuário atual
            $user_id = get_current_user_id();
    
            // Verifica o tipo de tela atual
            $screen = get_current_screen();
            $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
    
            // Filtrar produtos
            if ( $screen->id === 'edit-product' || is_post_type_archive( 'product' ) ) {
                $query->set( 'author', $user_id );
            }
    
            // Filtrar pedidos
            if ( $screen->id === 'edit-shop_order' || $screen->id === 'shop_order' ) {
                // Define a meta query para filtrar pelo campo personalizado "_vendor_id" correspondente ao ID do vendor
                $meta_query = array(
                    array(
                        'key'     => '_vendor_id',
                        'value'   => $user_id,
                        'compare' => '=',
                    ),
                );
    
                $query->set( 'meta_query', $meta_query );
            }
        }
    }
    
    
    
    function filter_vendor_media_library_list_mode( $where, $query ) {
        // Verifica se o usuário atual é um 'vendor'
        if ( current_user_can( 'vendor' ) && $query->is_main_query() && $query->get( 'post_type' ) === 'attachment' ) {
            // Obtém o ID do usuário atual
            $user_id = get_current_user_id();
    
            // Adiciona a restrição para mostrar apenas os arquivos do usuário atual
            global $wpdb;
            $where .= $wpdb->prepare( " AND $wpdb->posts.post_author = %d", $user_id );
        }
    
        return $where;
    }    
    
    function filter_vendor_media_library( $args ) {
        // Verifica se o usuário atual é um 'vendor'
        if ( current_user_can( 'vendor' ) ) {
            // Obtém o ID do usuário atual
            $user_id = get_current_user_id();

            // Adiciona a restrição para mostrar apenas os arquivos do usuário atual
            $args['author'] = $user_id;
        }

        return $args;
    }
    
    function exibir_vendor_id_pedido($order) {
        $vendor_id = $order->get_meta('_vendor_id');
        
        if (!empty($vendor_id)) {
            echo 'Vendor ID: ' . $vendor_id;
        }
    }

    function vincular_vendor_a_pedido($order) {
        $items = $order->get_items();

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $vendor_id = get_post_meta($product_id, '_vendor_id', true);

            if ($vendor_id) {
                $order->update_meta_data('_vendor_id', $vendor_id);
                break;
            }
        }
        $order->save();
    }

    // Adicione este código ao seu arquivo functions.php ou a um plugin personalizado
    public static function salvar_vendor_id_produto($product_id) {
        if (current_user_can('vendor')) {
            $user_id = get_current_user_id();
            update_post_meta($product_id, '_vendor_id', $user_id);
        }
    }
    
    public static function remove_menu_items_for_vendor() {

        global $submenu;
        global $menu;


        if (current_user_can('vendor')) {
            // Percorra os itens do menu
            foreach ($menu as $key => $item) {
                // Verifique se o item é o menu do WooCommerce
                if ($item[2] === 'woocommerce') {
                    // Altere o nome do menu principal para 'Meus Produtos'
                    $menu[$key][0] = 'Gerenciamento';
                    // Altere o ícone do menu principal para 'dashicons-products' (ou o ícone desejado)
                    $menu[$key][6] = 'dashicons-admin-generic';
                    break;
                }
            }
        }

        if ( current_user_can( 'vendor' ) ) {
            // Adiciona um item de menu personalizado para "Meus Pedidos"
            add_menu_page(
                __( 'Meus Pedidos', 'textdomain' ),
                __( 'Meus Pedidos', 'textdomain' ),
                'read',
                'edit.php?post_type=shop_order&view-order=mine',
                '',
                'dashicons-cart',
                26
            );
        }
        
        // Verifica se o usuário atual é um vendedor
        if ( current_user_can( 'vendor' ) ) {

            
            // Remove itens de menu e submenus específicos
            remove_menu_page( 'tools.php' ); // Ferramentas
            remove_menu_page( 'edit-comments.php' ); // Comentários
            remove_menu_page( 'edit.php' ); // Posts
            remove_menu_page( 'edit.php?post_type=elementor_library' );
        }

        return $menu;

    }
    

    public static function custom_product_subsubsub( $views ) {
        // Verifica se o usuário atual é um vendedor
        if ( current_user_can( 'vendor' ) ) {
            $user_id = get_current_user_id();
            
            // Obtem o total de produtos do vendedor
            $total_products = get_posts( array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'author' => $user_id,
                'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
                'fields' => 'ids',
            ) );
            
            $publish_count = count( $total_products );
            
            // Remove o subsubsub original
            unset( $views['all'] );
            unset( $views['publish'] );
            unset( $views['draft'] );
            unset( $views['pending'] );
            unset( $views['trash'] );
            
            // Adiciona a nova contagem
            $views['publish'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url( 'edit.php?post_type=product&post_status=publish&author=' . $user_id ),
                'publish' === get_query_var( 'post_status' ) ? ' class="current"' : '',
                __( 'Published' ),
                $publish_count
            );
        }
        
        return $views;
    }

    public static function add_vendor_product_caps() {
        $role = get_role( 'vendor' );
        $role->add_cap( 'edit_products' );
        $role->add_cap( 'edit_published_products' );
        $role->add_cap( 'edit_others_products' );
        $role->add_cap( 'publish_products' );
        $role->add_cap( 'read_products' );
        $role->add_cap( 'read_private_products' );
        $role->add_cap( 'delete_products' );
        $role->add_cap( 'delete_private_products' );
        $role->add_cap( 'delete_published_products' );
        $role->add_cap( 'delete_others_products' );
        $role->add_cap( 'view_vendor_orders' );
        $role->add_cap( 'view_orders' );
        $role->add_cap( 'view_woocommerce_reports' );
        $role->add_cap( 'manage_woocommerce_orders' );
        $role->add_cap( 'read_private_shop_orders' );
        $role->add_cap( 'view_shop_order' );

        $role->add_cap( 'edit_shop_order' );
        $role->add_cap( 'read_shop_order' );
        $role->add_cap( 'delete_shop_order' );
        $role->add_cap( 'edit_shop_orders' );
        $role->add_cap( 'edit_others_shop_orders' );
        $role->add_cap( 'publish_shop_orders' );
        $role->add_cap( 'read_private_shop_orders' );
        $role->add_cap( 'delete_shop_orders' );
        $role->add_cap( 'delete_private_shop_orders' );
        $role->add_cap( 'delete_published_shop_orders' );
        $role->add_cap( 'delete_others_shop_orders' );

        $role->add_cap( 'upload_files' );
        $role->add_cap( 'read' );
        $role->add_cap( 'edit_published_products ');

        $role->add_cap( 'read_private_shop_orders');
        $role->add_cap( 'edit_shop_orders');
        $role->add_cap( 'edit_others_shop_orders');
        $role->add_cap( 'edit_published_shop_orders');
        $role->add_cap( 'publish_shop_orders');
        $role->add_cap( 'read_shop_order');
        $role->add_cap( 'delete_shop_orders');
        $role->add_cap( 'delete_private_shop_orders');
        $role->add_cap( 'delete_published_shop_orders');
        $role->add_cap( 'delete_others_shop_orders');
        
    }


    public static function add_custom_vendor_field($post_id) {

        global $post;
        
        // Verifique se o usuário atual é um administrador
        if (current_user_can('administrator')) {
 
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