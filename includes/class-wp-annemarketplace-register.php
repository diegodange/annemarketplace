<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Register {
    private static $instance;

    public static function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_shortcode( 'custom_registration_form', array( $this, 'custom_registration_form_shortcode' ) );
        add_action( 'admin_post_nopriv_custom_registration', array( $this, 'custom_registration_form_handler' ) );
        add_action( 'admin_post_custom_registration', array( $this, 'custom_registration_form_handler' ) );
        add_action( 'wp_ajax_check_username', array( $this, 'check_username' ) );
        add_action( 'wp_ajax_nopriv_check_username', array( $this, 'check_username' ) );
    }

    // Criação do shortcode para exibir o formulário de cadastro
    public function custom_registration_form_shortcode() {
        ob_start();
        $this->custom_registration_form();
        return ob_get_clean();
    }

    // Criação do formulário de cadastro
    public function custom_registration_form() {
        ?>
        <form id="registration-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="needs-validation mt-5 mb-5" novalidate>
            <input type="hidden" name="action" value="custom_registration">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Nome de usuário:</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira um nome de usuário.
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">E-mail:</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira um endereço de e-mail válido.
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Senha:</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira uma senha.
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="user_type" class="form-label">Tipo de usuário:</label>
                    <select name="user_type" id="user_type" class="form-select" required>
                        <option value="">Selecione um tipo de usuário</option>
                        <option value="vendedor">Vendedor</option>
                    </select>
                    <div class="invalid-feedback">
                        Por favor, selecione um tipo de usuário.
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Registrar</button>
            <div id="form-message"></div>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('#registration-form').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);

                // Realiza validações antes de enviar a requisição AJAX
                if (!form[0].checkValidity()) {
                    form[0].reportValidity();
                    return;
                }

                // Verifica se o nome de usuário já existe
                var username = $('#username').val();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',
                    data: {
                        action: 'check_username',
                        username: username
                    },
                    success: function(response) {
                        if (response === 'exists') {
                            $('#form-message').html('<div class="alert alert-danger mt-3">O nome de usuário já está em uso.</div>');
                        } else {
                            // Nome de usuário disponível, continua com o registro
                            registerUser();
                        }
                    },
                    error: function(xhr) {
                        $('#form-message').html('<div class="alert alert-danger mt-3">Ocorreu um erro ao verificar o nome de usuário.</div>');
                    }
                });
            });

            // Função para registrar o usuário
            function registerUser() {
                var form = $('#registration-form');
                var formData = form.serialize();

                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url( admin_url('admin-post.php') ); ?>',
                    data: formData,
                    success: function(response) {
                        // Exibe mensagem de sucesso
                        $('#form-message').html('<div class="alert alert-success">Registro realizado com sucesso.</div>');
                        form[0].reset();
                    },
                    error: function(xhr) {
                        // Exibe mensagem de erro
                        $('#form-message').html('<div class="alert alert-danger">Ocorreu um erro ao registrar o usuário.</div>');
                    }
                });
            }
        });
        </script>
        <?php
    }

    // Verifica se o nome de usuário já existe
    public function check_username() {
        if ( isset( $_POST['username'] ) ) {
            $username = sanitize_user( $_POST['username'] );
            $user_id = username_exists( $username );

            if ( $user_id ) {
                echo 'exists';
            } else {
                echo 'available';
            }
        }

        wp_die();
    }

    // Processamento do formulário de cadastro
    public function custom_registration_form_handler() {
        if ( isset( $_POST['action'] ) && $_POST['action'] == 'custom_registration' ) {
            $username = sanitize_user( $_POST['username'] );
            $email = sanitize_email( $_POST['email'] );
            $password = $_POST['password'];
            $user_type = $_POST['user_type'];

            $userdata = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'role' => $user_type,
            );

            $user_id = wp_insert_user( $userdata );

            if ( ! is_wp_error( $user_id ) ) {
                // Registro bem-sucedido
                wp_send_json_success();
            } else {
                // Ocorreu um erro ao registrar o usuário
                wp_send_json_error();
            }
        }
    }
}

Register::getInstance();
