jQuery( document ).ready(function() {
    jQuery('#billing_cpf').mask('000.000.000-00', {reverse: true});
    jQuery('#billing_cellphone').mask('(00) 00000-0000');
    jQuery('#billing_birthdate').mask('00/00/0000');
    jQuery('#billing_postcode').mask('00000-000');

});

