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

        add_action( 'before_delete_post', [$this,'delete_product_images'], 10, 1 );
        add_action( 'template_redirect', [$this,'restrict_access_to_homepage'], 10, 1);

        wp_enqueue_script( 'jQuery_Mask_JS', ANNEMARKETPLACE_ADMIN_JS.'jquery.mask.js', array( 'jquery' ) );
        wp_enqueue_script( 'jQuery_Mask_Min_JS', ANNEMARKETPLACE_ADMIN_JS.'jquery.mask.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'Anne_JS', ANNEMARKETPLACE_ADMIN_JS.'form_checkout_v3.js', array( 'jquery' ) );
        wp_enqueue_script( 'Menu_JS', ANNEMARKETPLACE_ADMIN_JS.'menu_v9.js', array( 'jquery' ) );

        add_action( 'wp_body', [$this, 'restrict_access_to_homepage'] );

    }


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
