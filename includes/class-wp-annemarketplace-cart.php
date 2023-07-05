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
        add_filter('woocommerce_cart_item_name', [$this,'adicionar_informacoes_vendedor_carrinho'], 10, 3);
        add_action('woocommerce_single_product_summary', [$this,'adicionar_informacoes_vendedor_pagina_produto'], 6);
    }

    // Adiciona as informações do vendedor aos produtos do carrinho
    function adicionar_informacoes_vendedor_carrinho($product_name, $cart_item, $cart_item_key) {
        $product_id = $cart_item['product_id'];
        $vendor_id = get_post_field('post_author', $product_id);
        $shop_name = get_user_meta($vendor_id, 'store_name', true);
        $vendor_name = get_the_author_meta('display_name', $vendor_id);

        if ($shop_name) {
            $product_name .= '<br><small><b>Produtor </b> ' . $shop_name . '</small>';
        }

        if (!$shop_name) {
            $product_name .= '<br><small><b>Produtor </b> ' . $vendor_name . '</small>';
        }

        return $product_name;
    }

    // Adiciona as informações do vendedor na página do produto
    function adicionar_informacoes_vendedor_pagina_produto() {
        global $product;

        $vendor_id = get_post_field('post_author', $product->get_id());
        $shop_name = get_user_meta($vendor_id, 'store_name', true);
        $vendor_name = get_the_author_meta('display_name', $vendor_id);

        if ($shop_name) {
            echo '<div class="vendor-info"><b>Produtor:</b> ' . $shop_name . '</div>';
        }

        if (!$shop_name) {
            echo '<div class="vendor-info"><b>Vendedor:</b> ' . $vendor_name . '</div>';
        }
    }


}

Cart::getInstance();
