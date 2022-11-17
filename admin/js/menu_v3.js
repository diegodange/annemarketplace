jQuery( document ).ready(function() {

    jQuery('.elementor-menu-toggle').click(function (e) {
        console.log(this);
        jQuery('.elementor-menu-toggle').addClass('ativado');
        jQuery('.elementor-menu-toggle').addClass('elementor-active');
    })

});