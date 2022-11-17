jQuery( document ).ready(function() {

    jQuery('.elementor-menu-toggle').click(function (e) {
        console.log('ATIVADO');
        jQuery('.elementor-menu-toggle').addClass('ativado');
        jQuery('.elementor-menu-toggle').addClass('elementor-active');
    })

    jQuery('.ativado').click(function () {
        console.log('DESATIVADO');
        jQuery('nav.elementor-nav-menu--dropdown').toggle();
        jQuery('.elementor-menu-toggle').removeClass('ativado');
    })

});