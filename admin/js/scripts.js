jQuery(document).ready(function($) {
    var activityPanel = jQuery('#activity-panel-tab-activity');
    if (activityPanel.length > 0) {
        activityPanel.remove();
    }
    $('#_vendor_id').select2({
        maximumSelectionLength: 1
    } );
});