(function ($, Drupal) {
    'use strict';
    Drupal.behaviors.viewsSelectLigne = {
        attach: function (context, settings) {
            var app = {};

            function initApp() {
                app.drupal = drupalSettings.gepsis;
                app.$clickableRow = $('.clickable-row');
            }

            function attachEvents() {
                app.$clickableRow.once().click(clickableRowFeature);
            }

            function clickableRowFeature(ev) {
                var $this = $(this);
                var urlToOpen = $this.find('a').attr('href');
                if (urlToOpen) {
                    if (!urlToOpen.includes('trav-details'))
                        Drupal.webformOpenDialog(urlToOpen, 'webform-dialog-wide');
                    else
                        window.location = urlToOpen;
                }
            }

            // Here you can have others behaviors, "your_module_namespace" must be
            // unique ! Behaviors can be called many time, if you don't want reboot
            // your app you can use an initialized variable
            Drupal.behaviors.gepsis = {
                attach: function (context, settings) {
                    if (!app.initialized) {
                        initApp();
                        attachEvents();
                        app.initialized = true;
                    }
                }
            };
        }
    };
})(jQuery, Drupal);

/*
// https://drupal.stackexchange.com/questions/289899/how-to-open-a-link-in-ctools-modal-by-clicking-a-views-row-in-table-format
// https://gorannikolovski.com/blog/display-modal-page-load-drupal
var ajaxSettings = {
    type: 'POST',
    url: '/open-modal',
    dataType: 'json',
    data: {
        url: urlToOpenModal
    },
};
var myAjaxObject = Drupal.ajax(ajaxSettings);
myAjaxObject.execute();
*/