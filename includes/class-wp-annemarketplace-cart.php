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
        add_action('woocommerce_single_product_summary', [$this,'adicionar_informacoes_vendedor_pagina_produto'], 6);
        add_filter('woocommerce_get_item_data', [$this,'preencher_coluna_nome_loja_carrinho'], 10, 2);
    }

    // Preencher a coluna com o nome da loja ou nome do dono do produto
    function preencher_coluna_nome_loja_carrinho($cart_item_data, $cart_item) {
        $product_id = $cart_item['product_id'];
        $vendor_id = get_post_field('post_author', $product_id);
        $shop_name = get_user_meta($vendor_id, 'store_name', true);

        if (empty($shop_name)) {
            $shop_name = get_the_author_meta('display_name', $vendor_id);
        }

        if ($shop_name) {
            $cart_item_data['nome_loja'] = array(
                'key'   => 'Produtor',
                'value' => $shop_name,
            );
        }

        return $cart_item_data;
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

        if (empty($shop_name)) {
            echo '<div class="vendor-info"><b>Vendedor:</b> ' . $vendor_name . '</div>';
        }
    }


}

Cart::getInstance();
