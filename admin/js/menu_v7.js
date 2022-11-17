jQuery( document ).ready(function() {

    jQuery('.elementor-menu-toggle').click(function () {
        jQuery('.elementor-menu-toggle').addClass('elementor-active');
    })

    jQuery('.elementor-active').click(function () {
        jQuery('.elementor-nav-menu--dropdown').toggle();
    })
});