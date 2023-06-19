<?php
if (!defined('ABSPATH')) exit;

class Register
{
    private static $instance;

    public static function getInstance()
    {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_shortcode('custom_registration_form', array($this, 'custom_registration_form_shortcode'));
        add_action('admin_post_nopriv_custom_registration', array($this, 'custom_registration_form_handler'));
        add_action('admin_post_custom_registration', array($this, 'custom_registration_form_handler'));
        add_action('wp_ajax_check_email', array($this, 'check_email'));
        add_action('wp_ajax_nopriv_check_email', array($this, 'check_email'));
    }

    // Criação do shortcode para exibir o formulário de cadastro
    public function custom_registration_form_shortcode()
    {
        ob_start();
        $this->custom_registration_form();
        return ob_get_clean();
    }

    // Criação do formulário de cadastro
    public function custom_registration_form()
    {
        ?>
        <form id="registration-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation mt-2 mb-2" novalidate>
            <input type="hidden" name="action" value="custom_registration">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="name" class="form-label">Nome:</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira o nome.
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">E-mail:</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira um endereço de e-mail válido.
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="company_name" class="form-label">Nome da Empresa:</label>
                    <input type="text" name="company_name" id="company_name" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira o nome da empresa.
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="cnpj" class="form-label">CNPJ:</label>
                    <input type="text" name="cnpj" id="cnpj" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira o CNPJ.
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="password" class="form-label">Senha:</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, insira uma senha.
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="password_confirmation" class="form-label">Confirmar Senha:</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    <div class="invalid-feedback">
                        Por favor, confirme a senha.
                    </div>
                </div>
            </div>

            <div class="form-check mb-3 mt-3 d-flex align-items-center">
                <input type="checkbox" class="form-check-input" name="accept_terms" id="accept_terms" required style="cursor: pointer;">
                <label class="form-check-label ms-2 mt-1" for="accept_terms">Aceitar termos e condições</label>
                <div class="invalid-feedback">
                    Você deve aceitar os termos e condições.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Registrar</button>
            <div id="form-message"></div>
        </form>

        <script>
            jQuery(document).ready(function ($) {
                $('#registration-form').on('submit', function (e) {
                    e.preventDefault();

                    var form = $(this);

                    // Realiza validações antes de enviar a requisição AJAX
                    if (!form[0].checkValidity()) {
                        form[0].reportValidity();
                        return;
                    }

                    // Verifica se o e-mail já existe
                    var email = $('#email').val();
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        data: {
                            action: 'check_email',
                            email: email
                        },
                        success: function (response) {
                            if (response === 'exists') {
                                $('#form-message').html('<div class="alert alert-danger mt-3">O e-mail já está em uso.</div>');
                            } else {
                                // E-mail disponível, continua com o registro
                                registerUser();
                            }
                        },
                        error: function (xhr) {
                            $('#form-message').html('<div class="alert alert-danger mt-3">Ocorreu um erro ao verificar o e-mail.</div>');
                        }
                    });
                });

                // Função para registrar o usuário
                function registerUser() {
                    var form = $('#registration-form');
                    var password = $('#password').val();
                    var passwordConfirmation = $('#password_confirmation').val();

                    // Verifica se as senhas coincidem
                    if (password !== passwordConfirmation) {
                        $('#form-message').html('<div class="alert alert-danger">As senhas não coincidem.</div>');
                        return;
                    }

                    var formData = form.serialize();

                    $.ajax({
                        type: 'POST',
                        url: '<?php echo esc_url(admin_url('admin-post.php')); ?>',
                        data: formData,
                        success: function (response) {
                            // Exibe mensagem de sucesso
                            $('#form-message').html('<div class="alert alert-success">Registro realizado com sucesso.</div>');
                            form[0].reset();
                        },
                        error: function (xhr) {
                            // Exibe mensagem de erro
                            $('#form-message').html('<div class="alert alert-danger">Ocorreu um erro ao registrar o usuário.</div>');
                        }
                    });
                }
            });
        </script>
        <?php
    }

    // Verifica se o e-mail já existe
    public function check_email()
    {
        if (isset($_POST['email'])) {
            $email = sanitize_email($_POST['email']);
            $user = get_user_by('email', $email);

            if ($user) {
                echo 'exists';
            } else {
                echo 'available';
            }
        }

        wp_die();
    }

    // Processamento do formulário de cadastro
    public function custom_registration_form_handler()
    {
        if (isset($_POST['action']) && $_POST['action'] == 'custom_registration') {
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $password = $_POST['password'];
            $company_name = sanitize_text_field($_POST['company_name']);
            $cnpj = sanitize_text_field($_POST['cnpj']);

            // Verifica se o checkbox de aceitar os termos foi marcado
            if (!isset($_POST['accept_terms'])) {
                wp_send_json_error('Você deve aceitar os termos e condições.');
            }

            // Gera o nome de usuário com base no e-mail
            $username = sanitize_user(current(explode('@', $email)), true);
            $userdata['user_login'] = $username;

            $userdata = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'role' => 'vendor',
                'first_name' => $name,
            );

            $user_id = wp_insert_user($userdata);

            if (!is_wp_error($user_id)) {
                // Registro bem-sucedido
                update_user_meta($user_id, 'store_name', $company_name);
                update_user_meta($user_id, 'store_slug', $cnpj);
                wp_send_json_success();
            } else {
                // Ocorreu um erro ao registrar o usuário
                wp_send_json_error('Ocorreu um erro ao registrar o usuário.');
            }
        }
    }
}

Register::getInstance();
?>
