jQuery( document ).ready(function() {

    jQuery('.elementor-menu-toggle').click(function (e) {
        console.log('TOGGLE');
        jQuery('.elementor-nav-menu').toggle();
    })

});