<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class Users_Function{
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
        self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('user_register', [$this,'set_default_admin_color_for_new_user']);
        add_action('admin_init', [$this,'set_default_admin_color_for_existing_users']);
        add_action('admin_footer', [$this,'custom_user_profile_fields']);

    }

    // Defina a paleta de cores "Light" como padrão para novos usuários e usuários existentes

    function set_default_admin_color_for_new_user($user_id) {
        update_user_meta($user_id, 'admin_color', 'light');
    }

    function set_default_admin_color_for_existing_users() {
        // Obtenha todos os usuários existentes e defina a paleta de cores "Light" para cada um deles
        $users = get_users();
        foreach ($users as $user) {
            update_user_meta($user->ID, 'admin_color', 'light');
        }
    }

    // Adicione filtro para o formulário do perfil do usuário

    function custom_user_profile_fields() {
        // Remova todas as opções de paleta de cores do formulário, exceto a "Light"
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('#color-picker .color-option').not('.selected').remove();
                $('#color-picker .color-option.selected').find('input[type="radio"]').attr('checked', true);
            });
        </script>
        <?php
    }

}
    Users_Function::getInstance();