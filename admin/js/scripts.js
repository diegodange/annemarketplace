jQuery(document).ready(function($) {
    var activityPanel = jQuery('#activity-panel-tab-activity');
    if (activityPanel.length > 0) {
        activityPanel.remove();
    }

    $('#your-profile h2').each(function() {
        if ($(this).text() === 'Opções pessoais') {
          $(this).remove();
        }
    });
    
    $('.user-rich-editing-wrap').remove();
    $('.user-comment-shortcuts-wrap').remove();
    $('.user-admin-bar-front-wrap').remove();
    $('.user-language-wrap').remove();
    $('#application-passwords-section').remove();
      
    $('#_vendor_id').select2({
        maximumSelectionLength: 1
    } );
});