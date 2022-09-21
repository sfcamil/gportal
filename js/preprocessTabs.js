(function ($, Drupal, drupalSettings) {
    'use strict';
    Drupal.behaviors.preprocessTabs = {
        attach: function (context, settings) {
            // $(document).ready(function () {
            $(document, context).once('preprocessTabs').ready(function () {
                var var_url = location.href;
                if (var_url)
                    var var_ParseURL = var_url.split("#");
                if (var_ParseURL[1]) {
                    var index = var_ParseURL[1].replace('lb-tabs-tabs-', '');
                    $('#lb-tabs-tabs').tabs(); // first tab selected
                    $('#main-container').hide();
                    $('#lb-tabs-tabs').tabs("option", "active", index-1);
                    $('#main-container').show();
                }
            });
        }
    };
})(jQuery, Drupal, drupalSettings);
