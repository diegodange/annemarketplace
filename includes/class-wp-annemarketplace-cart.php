<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Cart{
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // add_action('woocommerce_proceed_to_checkout', [$this,'verificar_vendedores_no_carrinho']);
        // add_action('woocommerce_before_cart', [$this,'exibir_mensagem_erro_vendedores']);
    }

    // function verificar_vendedores_no_carrinho() {
    //     $cart_items = WC()->cart->get_cart();

    //     if (count($cart_items) > 1) {
    //         $first_vendor_id = null;

    //         foreach ($cart_items as $cart_item_key => $cart_item) {
    //             $product_id = $cart_item['product_id'];
    //             $vendor_id = get_post_meta($product_id, '_vendor_id', true);

    //             if ($first_vendor_id === null) {
    //                 $first_vendor_id = $vendor_id;
    //             } elseif ($vendor_id !== $first_vendor_id) {
    //                 remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
    //                 break;
    //             }
    //         }
    //     }
    // }   

    // function exibir_mensagem_erro_vendedores() {
    //     $cart_items = WC()->cart->get_cart();

    //     if (count($cart_items) > 1) {
    //         $first_vendor_id = null;

    //         foreach ($cart_items as $cart_item_key => $cart_item) {
    //             $product_id = $cart_item['product_id'];
    //             $vendor_id = get_post_meta($product_id, '_vendor_id', true);

    //             if ($first_vendor_id === null) {
    //                 $first_vendor_id = $vendor_id;
    //             } elseif ($vendor_id !== $first_vendor_id) {
    //                 wc_print_notice(__('Você só pode comprar produtos de um único vendedor por vez.'), 'error');
    //                 break;
    //             }
    //         }
    //     }
    // }

}

Cart::getInstance();
