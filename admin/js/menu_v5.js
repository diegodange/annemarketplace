jQuery( document ).ready(function() {

    jQuery('.elementor-menu-toggle').click(function (e) {
        console.log('TOGGLE');
        jQuery('nav.elementor-nav-menu--dropdown').toggle();
    })

});