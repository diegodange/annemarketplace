<?php

    define( 'ANNEMARKETPLACE_VERSION', '1.1.2' );
    define( 'ANNEMARKETPLACE_MIN_PHP_VER', '7.3.0' );
    define( 'ANNEMARKETPLACE_MIN_WC_VER', '4.7.0' );
    define( 'ANNEMARKETPLACE_MAIN_FILE', __FILE__ );
    define( 'ANNEMARKETPLACE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
    define( 'ANNEMARKETPLACE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

    define( 'ANNEMARKETPLACE_ADMIN_JS', plugin_dir_url( __DIR__ )."admin/js/" );
    define( 'ANNEMARKETPLACE_ADMIN_CSS', plugin_dir_url( __DIR__ )."admin/css/" );

