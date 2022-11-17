jQuery( document ).ready(function() {

    jQuery('.elementor-menu-toggle__icon--open').click(function () {
        jQuery('.elementor-menu-toggle').addClass('elementor-active');
    })

    jQuery('.elementor-menu-toggle__icon--close').click(function () {
        jQuery('.elementor-menu-toggle').removeClass('elementor-active');
    })
});