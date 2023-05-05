<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Options{
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_filter( 'woocommerce_product_tabs',  [$this,'Remover_Tabs_Single_Product'] ,98 );
        add_filter('woocommerce_checkout_fields', [$this,'Campos_Personalizados_Checkout'], 10);
        add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false');
        add_filter( 'woocommerce_checkout_fields', [$this,'Adicionar_Campos'], 11);
        // add_filter('add_to_cart_redirect', [$this,'lw_add_to_cart_redirect']);

        add_action( 'before_delete_post', [$this,'delete_product_images'], 10, 1 );
        add_action( 'template_redirect', [$this,'restrict_access_to_homepage'], 10, 1);

        wp_enqueue_script( 'jQuery_Mask_JS', ANNEMARKETPLACE_ADMIN_JS.'jquery.mask.js', array( 'jquery' ) );
        wp_enqueue_script( 'jQuery_Mask_Min_JS', ANNEMARKETPLACE_ADMIN_JS.'jquery.mask.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'Anne_JS', ANNEMARKETPLACE_ADMIN_JS.'form_checkout_v3.js', array( 'jquery' ) );
        wp_enqueue_script( 'Menu_JS', ANNEMARKETPLACE_ADMIN_JS.'menu_v9.js', array( 'jquery' ) );

        add_shortcode( 'vendor_list', [$this,'list_vendors']);

    }

          // $custom_fields = get_posts([
            // 	'post_type' => 'marketking_field',
            //   	'post_status' => 'publish',
            //   	'numberposts' => -1,
            //   	'meta_key' => 'marketking_field_sort_number',
            //     'orderby' => 'meta_value_num',
            //     'order' => 'ASC',
            //   	'meta_query'=> array(
            //   		'relation' => 'AND',
            //         array(
            //             'key' => 'marketking_field_status',
            //             'value' => 1
            //         ),
            // 	)
            // ]);

            // $users = get_users(array(
			//     'meta_key'     => 'marketking_group',
			//     'meta_value'   => 'none',
			//     'meta_compare' => '!=',
			// ));
            // $user_id = intval($users[0]->data->ID);

            // $meta_fields = get_user_meta( $user_id );

            // foreach ( $meta_fields as $key => $value ) {
            //     echo '<p>' . $key . ': ' . $value[0] . '</p>';
            // }

    public static function list_vendors(){
      
        $vendors = marketking()->get_all_vendors();

        $vendor_count = count($vendors);
        $vendors_per_page = 4; // Defina o número de vendedores que deseja exibir por página
        $total_pages = ceil($vendor_count / $vendors_per_page);

        echo '<div class="container px-0">';
        echo '<div class="row g-4">';

        $page = get_query_var('page') ? get_query_var('page') : 1;
        $offset = ($page - 1) * $vendors_per_page;
        $vendors_to_display = array_slice($vendors, $offset, $vendors_per_page);

        echo '<p>Mostrando ' . count($vendors_to_display) . ' de ' . $vendor_count . ' vendedores</p>';


        foreach ( $vendors_to_display as $user ) {
            echo '<div class="col-xxl-3">';
                echo '<div class="card">';
                    $user_id = $user->ID;
                    $store_name = marketking()->get_store_name_display($user_id);
                    $store_link = marketking()->get_store_link($user_id);
                    $profile_pic = get_user_meta($user_id,'marketking_profile_logo_image', true);
                    echo '<img src="'.$profile_pic.'" class="card-img-top" alt="'.$store_name.'">';
                    echo '<div class="card-body">
                        <h5 class="card-title">  <a href='.$store_link.'>'.$store_name.'</a> </h5> 
                        <a href='.$store_link.' class="btn btn-primary mt-4" target="__blank"> ACESSAR </a> 
                    </div>';
                echo '</div>';
            echo '</div>';
        }

            $total_pages = ceil($vendor_count / $vendors_per_page); // número total de páginas

            $paginate_args = array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => 'page/%#%',
            'current' => $paged,
            'total' => $total_pages,
            'prev_text' => 'Anterior',
            'next_text' => 'Próxima',
            'mid_size' => 1
            );
            echo '<div class="pagination">';
                echo paginate_links($paginate_args);
            echo '</div>';

        echo '</div>';
        echo '</div>';

    // var_dump($custom_fields->post_title);
    
    }

    // public static function lw_add_to_cart_redirect() {
    //     global $woocommerce;
    //     $lw_redirect_checkout = $woocommerce->cart->get_checkout_url();
    //     return $lw_redirect_checkout;
    // }
    public static function restrict_access_to_homepage() {
        if ( !current_user_can( 'administrator' ) && is_page('home') ) {
        echo '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border: 1px solid #ccc;">Você não tem permissão para acessar esta página....</div>';
        exit;
        }
      }


    public static function delete_product_images( $post_id ) {
        $product = wc_get_product( $post_id );

        if ( !$product ) {
            return;
        }

        $featured_image_id = $product->get_image_id();
        $image_galleries_id = $product->get_gallery_image_ids();

        if( !empty( $featured_image_id ) ) {
            wp_delete_post( $featured_image_id );
        }

        if( !empty( $image_galleries_id ) ) {
            foreach( $image_galleries_id as $single_image_id ) {
                wp_delete_post( $single_image_id );
            }
        }
    }

    public static function Adicionar_Campos( $fields ) {

        $fields['billing']['billing_email']   = array(
            'label'        => 'E-mail Principal',
            'required'     => true,
            'class'        => array( 'form-row-wide', 'my-custom-class' ),
            'priority'     => 5,
            'placeholder'  => 'Não se esqueça de inserir seu E-mail',
        );


        return $fields;

    }

    public static function Remover_Tabs_Single_Product( $tabs ) {            
        unset( $tabs['additional_information'] );    
        return $tabs;
    }

    public static function Campos_Personalizados_Checkout( $fields ) {

        // POSIÇÃO DOS CAMPOS
        $fields['billing']['billing_first_name']['priority'] = 1;
        $fields['billing']['billing_last_name']['priority'] = 2;
        $fields['billing']['billing_birthdate']['priority'] = 3;
        $fields['billing']['billing_cpf']['priority'] = 4;
        $fields['billing']['billing_cellphone']['priority'] = 6;
        // POSIÇÃO DOS CAMPOS

        // E-MAIL 
        unset($fields['billing']['billing_email']); 
        // E-MAIL

        // BAIRRO 
        $fields['billing']['billing_neighborhood']['required'] = true;
        // BAIRRO

        // CELULAR 
        $fields['billing']['billing_cellphone']['required'] = true;
        $fields['billing']['billing_cellphone']['class'] = array('form-row-wide'); 
        // CELULAR

        // CPF
        $fields['billing']['billing_cpf']['placeholder'] = 'Digite o seu CPF';
        $fields['billing']['billing_cpf']['required'] = true;
        $fields['billing']['billing_cpf']['label'] = 'CPF';
        $fields['billing']['billing_cpf']['class'] = array('form-row-last'); 
        // CPF

        // NÚMERO DA CASA
        $fields['billing']['billing_number']['label'] = 'Nº da Casa';
        // NÚMERO DA CASA
        
        // CAMPOS DESATIVADOS
        unset($fields['billing']['billing_company']); 
        unset($fields['billing']['billing_cnpj']); 
        unset($fields['billing']['billing_sex']); 
        unset($fields['billing']['billing_rg']); 
        unset($fields['billing']['billing_ie']);
        unset($fields['billing']['billing_phone']);
        // CAMPOS DESATIVADOS

        return $fields;
    }


}

Options::getInstance();
