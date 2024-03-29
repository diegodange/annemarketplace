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

        
        add_filter( 'upload_mimes', [$this,'restrict_vendor_upload_types']);

        // add_action( 'woocommerce_product_options_general_product_data', [$this,'add_custom_vendor_field']);
        // add_action( 'woocommerce_process_product_meta',  [$this,'save_custom_vendor_field']);

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

        add_action('wp_dashboard_setup', [$this, 'add_custom_widget']);
        add_action('wp_dashboard_setup', [$this, 'remove_vendor_widgets']);
        add_action('admin_init', [$this, 'remove_vendor_footer_text']);
        add_action('admin_head', [$this,'custom_admin_fonts']);
        add_action('admin_head', [$this,'custom_wpadminbar_fonts']);

        // Ação para processar a ação de salvamento das configurações da loja
        add_action('admin_post_salvar_configuracoes_loja', [$this,'processar_acao_salvar_configuracoes_loja']);
        add_action('admin_menu', [$this,'adicionar_pagina_configuracoes_loja_menu']);
        add_action('add_meta_boxes_shop_order', [$this,'remover_widget_campos_personalizados_pedido']);

        // add_filter('show_user_admin_color', '__return_false');
        add_action('add_meta_boxes', [$this,'remover_metabox_postcustom']);    
        add_action('wp_before_admin_bar_render', [$this,'remover_item_menu_novo']);
        // Remove o link para a página de comentários na barra de administração
        add_action('wp_before_admin_bar_render', [$this,'remover_link_comentarios']);

        add_filter( 'wp_is_application_passwords_available', '__return_false' );

        add_action('add_meta_boxes', [$this,'add_approval_custom_field']);
        add_action('save_post_product', [$this,'save_product_approval_field']);
        add_action('add_meta_boxes_product', [$this,'move_product_approval_metabox']);

        add_filter('product_type_selector', [$this,'restrict_product_type_options']);
        add_filter('product_type_options', [$this,'restrict_product_type_options_v2']);

        add_filter('woocommerce_product_data_tabs', [$this,'restrict_product_data_tabs'], 10, 1);
        add_filter('woocommerce_product_data_panels', '__return_empty_array', 10, 1);
  



    }  


    function restrict_vendor_upload_types( $mime_types ) {
        // Verifica se o usuário atual é um 'vendor'
        if ( current_user_can( 'vendor' ) ) {
            // Define apenas os tipos de arquivo de imagem permitidos
            $mime_types = array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif'          => 'image/gif',
                'png'          => 'image/png',
            );
        }
        return $mime_types;
    }
    
    function restrict_product_data_tabs($tabs) {
        // Verifica se o usuário é do tipo "vendor"
        if (current_user_can('vendor')) {
            // Remove as guias de dados do produto
            // unset($tabs['inventory']);
            // unset($tabs['shipping']);
            unset($tabs['linked_product']);
            unset($tabs['attribute']);
            unset($tabs['variations']);
            unset($tabs['advanced']);
        }
        
        return $tabs;
    }

    function restrict_product_type_options_v2($product_types) {
        // Verifica se o usuário é do tipo "vendor"
        if (current_user_can('vendor')) {
            // Remove as opções "Virtual" e "Baixável"
            unset($product_types['virtual']);
            unset($product_types['downloadable']);
        }
        return $product_types;
    }

    function restrict_product_type_options($product_types) {
        // Verifica se o usuário é do tipo "vendor"
        if (current_user_can('vendor')) {
            // Mantém somente a opção "Produto simples"
            $product_types = array(
                'simple' => __('Produto simples', 'woocommerce'),
            );
        }
    
        return $product_types;
    }
    
    // Adiciona o campo personalizado de aprovação aos produtos
    function add_approval_custom_field() {
        // Verifica se o usuário é um administrador
        if (!current_user_can('administrator')) {
            return;
        }
        // Adiciona o campo na página de edição do produto
        add_meta_box(
            'product_approval_field',
            'Aprovação do Produto',
            [$this,'display_product_approval_field'],
            'product',
            'normal',
            'default'
        );
    }

    // Exibe o campo personalizado de aprovação
    function display_product_approval_field($post) {
        // Verifica se o usuário é um administrador
        if (!current_user_can('administrator')) {
            return;
        }
        // Obtém o valor atual do campo personalizado
        $approval_status = get_post_meta($post->ID, '_approval_status', true);

        // Exibe o campo
        ?>
        <div class="options_group">
            <label for="product-approval-status">
                <input type="checkbox" id="product-approval-status" name="product_approval_status" value="1" <?php checked($approval_status, '1'); ?>>
                Produto Aprovado
            </label>
        </div>
        <?php
    }

    // Salva o valor do campo personalizado de aprovação
    function save_product_approval_field($post_id) {
        // Verifica se o usuário é um administrador
        if (!current_user_can('administrator')) {
            return;
        }

        if (isset($_POST['product_approval_status'])) {
            update_post_meta($post_id, '_approval_status', '1');
        } else {
            update_post_meta($post_id, '_approval_status', '0');
        }
    }

    // Move a metabox para a guia "Geral"
    function move_product_approval_metabox() {
        // Verifica se o usuário é um administrador
        if (!current_user_can('administrator')) {
            return;
        }
        remove_meta_box('product_approval_field', 'product', 'normal');
        add_meta_box('product_approval_field', 'Aprovação do Produto', [$this,'display_product_approval_field'], 'product', 'normal', 'default');
    }

    function remover_link_comentarios() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }

    function remover_item_menu_novo() {
        global $wp_admin_bar;

        // Remove o item "Novo" do menu
        $wp_admin_bar->remove_node('new-content');
    }

    function remover_metabox_postcustom() {
        remove_meta_box('postcustom', 'product', 'normal');
        remove_meta_box('slugdiv', 'product', 'normal');
        remove_meta_box('postexcerpt', 'product', 'normal');
    }

    function remover_widget_campos_personalizados_pedido() {
        remove_meta_box('postcustom', 'shop_order', 'normal');
        remove_meta_box('woocommerce-order-downloads', 'shop_order', 'normal');
    }

    // Função para exibir a página de configurações da loja
    function exibir_pagina_configuracoes_loja() {
        $user_id = get_current_user_id();

        if (current_user_can('edit_user', $user_id) && in_array('vendor', get_userdata($user_id)->roles)) {
            $store_name = get_user_meta($user_id, 'store_name', true);
            $store_address = get_user_meta($user_id, 'store_address', true);
            $store_slug = get_user_meta($user_id, 'store_slug', true);

            // Exibir mensagem de confirmação se o formulário foi salvo
            if (isset($_GET['saved']) && $_GET['saved'] === 'true') {
                echo '<div id="message" class="updated notice is-dismissible"><p>As configurações foram salvas com sucesso.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Fechar aviso</span></button></div>';
            }

            // Exibir o formulário de configurações da loja
            echo '<div class="wrap">';
            echo '<h1>Configurações da Loja</h1>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="salvar_configuracoes_loja">';
            wp_nonce_field('salvar_configuracoes_loja_nonce', 'salvar_configuracoes_loja_nonce');
            echo '<table class="form-table">';
            echo '<tr><th><label for="store_name">Nome da Loja</label></th><td><input type="text" id="store_name" name="store_name" value="' . esc_attr($store_name) . '"></td></tr>';
            echo '<tr><th><label for="store_address">Endereço da Loja</label></th><td><input type="text" id="store_address" name="store_address" value="' . esc_attr($store_address) . '"></td></tr>';
            echo '<tr><th><label for="store_slug">Slug da Loja</label></th><td><input type="text" id="store_slug" name="store_slug" value="' . esc_attr($store_slug) . '"></td></tr>';
            echo '</table>';
            echo '<p><input type="submit" class="button button-primary" value="Salvar Configurações"></p>';
            echo '</form>';
            echo '</div>';
        } else {
            echo '<div class="wrap"><h1>Permissão Negada</h1><p>Você não tem permissão para acessar esta página.</p></div>';
        }
    }

    // Função para processar a ação de salvamento das configurações da loja
    function processar_acao_salvar_configuracoes_loja() {
        if (isset($_POST['action']) && $_POST['action'] === 'salvar_configuracoes_loja') {
            // Verifica o nonce de segurança
            if (!isset($_POST['salvar_configuracoes_loja_nonce']) || !wp_verify_nonce($_POST['salvar_configuracoes_loja_nonce'], 'salvar_configuracoes_loja_nonce')) {
                wp_die('Erro de Segurança! Por favor, tente novamente.');
            }

            // Obtém o ID do usuário logado
            // Obtém o ID do usuário logado
            $user_id = get_current_user_id();

            // Verifica se o usuário tem permissão para editar as configurações da loja
            if (current_user_can('edit_user', $user_id) && in_array('vendor', get_userdata($user_id)->roles)) {
                // Obtém os dados do formulário
                $store_name = isset($_POST['store_name']) ? sanitize_text_field($_POST['store_name']) : '';
                $store_address = isset($_POST['store_address']) ? sanitize_text_field($_POST['store_address']) : '';
                $store_slug = isset($_POST['store_slug']) ? sanitize_title($_POST['store_slug']) : '';

                // Salva os dados da loja como meta dados do usuário
                update_user_meta($user_id, 'store_name', $store_name);
                update_user_meta($user_id, 'store_address', $store_address);
                update_user_meta($user_id, 'store_slug', $store_slug);

                // Redireciona para a página de configurações da loja com uma mensagem de confirmação
                wp_redirect(admin_url('admin.php?page=configuracoes_loja&saved=true'));
                exit;
            }
        }
    }
    
    // Função para adicionar a página de configurações da loja no menu principal
    function adicionar_pagina_configuracoes_loja_menu() {
        add_menu_page('Dados da Loja', 'Dados da Loja', 'vendor', 'configuracoes_loja', [$this,'exibir_pagina_configuracoes_loja'], 'dashicons-store', 30);
    }

    function custom_wpadminbar_fonts() {
        echo '<style type="text/css">
            #wpadminbar {
                font-family: "Instrument Sans", sans-serif !important;
            }
        </style>';
    }
    
    function custom_admin_fonts() {
        echo '<style type="text/css">
            body, #adminmenu, #adminmenu a, #adminmenu li.wp-menu-separator, #adminmenu li.wp-has-current-submenu, #adminmenu li.wp-menu-image, #adminmenu li.opensub, #adminmenu li.wp-has-submenu {
                font-family: "Instrument Sans", sans-serif !important;
            }
        </style>';
    }

    function remove_vendor_footer_text() {
        if (current_user_can('vendor')) {
            add_filter('admin_footer_text', '__return_empty_string', 11);
        }
    }

    function remove_vendor_widgets() {
        // Obtém o ID do usuário atual
        $user_id = get_current_user_id();
    
        // Verifica se o usuário possui a função "vendor"
        if (in_array('vendor', wp_get_current_user()->roles)) {
            // IDs dos widgets a serem removidos
            $widgets_to_remove = array(
                'dashboard_quick_press',    // Publicação Rápida
                'dashboard_primary',        // Central do WordPress
                'dashboard_secondary',      // Notícias e Eventos
                // Adicione mais IDs de widgets para removê-los
            );
    
            // Remove os widgets selecionados
            foreach ($widgets_to_remove as $widget_id) {
                remove_meta_box($widget_id, 'dashboard', 'side');
            }
            
            // IDs dos widgets do Elementor a serem removidos
            $elementor_widgets = array(
                'e-dashboard-overview',     // Visão geral do Elementor
                'e-dashboard-templates',    // Modelos do Elementor
                'e-dashboard-library',      // Biblioteca do Elementor
                // Adicione mais IDs de widgets do Elementor para removê-los
            );

            // Remove os widgets do Elementor
            foreach ($elementor_widgets as $widget_id) {
                remove_meta_box($widget_id, 'dashboard', 'normal');
            }
            remove_meta_box('dashboard_activity', 'dashboard', 'normal');
            remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

        }
    }
    
    // Exemplo de função para adicionar um widget personalizado
    public function add_custom_widget() {
        
        wp_add_dashboard_widget(
            'custom_widget_id',
            'Pedidos',
            [$this, 'custom_widget_content']
        );

    }

    // Exemplo de função para exibir o conteúdo do widget personalizado
    public function custom_widget_content() {
        // Obtém o ID do usuário atual
        $user_id = get_current_user_id();

        // Obtém todos os status de pedidos válidos
        $order_statuses = wc_get_order_statuses();

        // Inicializa um array para armazenar o total de pedidos em cada status
        $order_counts = array();

        // Obtém o total de pedidos em cada status
        foreach ($order_statuses as $status_key => $status_label) {
            $args = array(
                'post_type'      => 'shop_order',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_vendor_id',
                        'value'   => $user_id,
                        'compare' => '=',
                    ),
                ),
            );

            // Define o status atual para consulta
            $args['post_status'] = $status_key;

            $orders = get_posts($args);
            $order_counts[$status_key] = count($orders);
        }

        // Exibe o total de pedidos em cada status
        foreach ($order_counts as $status_key => $count) {
            $status_label = isset($order_statuses[$status_key]) ? $order_statuses[$status_key] : '';
            echo ucfirst($status_label) . ': ' . $count . '<br>';
        }
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
                'author'         => array($user_id, $user_id), // Filtra por ID do vendedor e ID do cliente
                'post_status'    => array('wc-processing', 'wc-completed', 'wc-on-hold', 'wc-cancelled'),
                'meta_query'     => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_vendor_id',
                        'value'   => $user_id,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_customer_user',
                        'value'   => $user_id,
                        'compare' => '=',
                    ),
                ),
            );
    
            $total_orders = get_posts($args);
            $processing_count = 0;
            $completed_count = 0;
            $on_hold_count = 0;
            $cancelled_count = 0;
    
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
                    case 'cancelled':
                        $cancelled_count++;
                        break;
                }
            }
    
            // Remove as contagens originais
            unset($views['all']);
            unset($views['wc-processing']);
            unset($views['wc-completed']);
            unset($views['wc-on-hold']);
            unset($views['wc-cancelled']);
    
            // Adiciona as novas contagens
            $views['wc-processing'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=wc-processing'),
                'processing' === get_query_var('post_status') ? ' class="current"' : '',
                __('Processando'),
                $processing_count
            );
    
            $views['wc-completed'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=wc-completed'),
                'completed' === get_query_var('post_status') ? ' class="current"' : '',
                __('Concluído'),
                $completed_count
            );
    
            $views['wc-on-hold'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=wc-on-hold'),
                'on-hold' === get_query_var('post_status') ? ' class="current"' : '',
                __('Em espera'),
                $on_hold_count
            );

            $views['wc-cancelled'] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                admin_url('edit.php?post_type=shop_order&post_status=wc-cancelled'),
                'cancelled' === get_query_var('post_status') ? ' class="current"' : '',
                __('Cancelado'),
                $cancelled_count
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
        
        $user = get_user_by('ID', $vendor_id);

        if ($user) {
            $username = $user->display_name; // Nome de usuário
            $user_email = $user->user_email; // Email do usuário

            // Faça o que for necessário com as informações do usuário
            echo '<strong class="mt-4"> Informações da Loja </strong> </br>';
            echo 'Vendedor: ' . $username. '</br>';
            echo "Email: " . $user_email . "</br>";
        }

    }

    function vincular_vendor_a_pedido($order) {
        $items = $order->get_items();

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $vendor_id = get_post_meta($product_id, '_vendor_id', true);

            if ($vendor_id) {
                $order->set_customer_id($vendor_id);
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
                    // // Altere o nome do menu principal para 'Meus Produtos'
                    // $menu[$key][0] = 'Gerenciamento';
                    // // Altere o ícone do menu principal para 'dashicons-products' (ou o ícone desejado)
                    // $menu[$key][6] = 'dashicons-admin-generic';
                    unset($menu[$key]);
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
        // $role->add_cap( 'view_woocommerce_reports' );
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
        $role->add_cap('edit_shop_order');
        // $role->add_cap('manage_product_terms');
        // $role->add_cap('edit_product_terms');
        // $role->add_cap('delete_product_terms');
        $role->add_cap('assign_product_terms');
        
    }
    
    // Adiciona uma nova coluna "Vendedor" à tabela de listagem de produtos
    public static function my_custom_products_table_column( $columns ) {
        $new_columns = array();
        foreach( $columns as $column_key => $column_value ) {
            $new_columns[ $column_key ] = $column_value;
            if ( 'product_tag' === $column_key ) { // Adiciona após a coluna "Tag"
                if (current_user_can('administrator')) {
                    $new_columns['product_vendor'] = __( 'Vendedor', 'woocommerce' );
                }
            }
        }
        return $new_columns;
    }

    // Exibe o nome do vendedor na coluna "Vendedor" da tabela de listagem de produtos
    public static function my_custom_products_table_column_content($column, $post_id) {
        if (current_user_can('administrator') && 'product_vendor' === $column) {
            // Obtém o ID do usuário do tipo "vendor" para o produto
            $vendor_ids = get_post_meta($post_id, '_vendor_id', true);
    
            // Certifique-se de que $vendor_ids é um array
            $vendor_ids = is_array($vendor_ids) ? $vendor_ids : array($vendor_ids);
    
            // Obtém uma lista de objetos WP_User
            $user_query = new WP_User_Query(array('role' => 'vendor'));
            $users = $user_query->get_results();
            
            $names = array();
    
            foreach ($vendor_ids as $vendor_id) {
                foreach ($users as $user) {
                    if ($vendor_id == $user->ID) {
                        $first_name = get_user_meta($user->ID, 'first_name', true);
                        $last_name = get_user_meta($user->ID, 'last_name', true);
                        $complete_name = $first_name . ' ' . $last_name;
                        $profile_url = get_edit_user_link($user->ID);
                        $names[] = '<a href="' . esc_url($profile_url) . '">' . esc_html($complete_name) . '</a>';
                    }
                }
            }
    
            echo implode(', ', $names);
        }
    }
    


}

Products::getInstance();