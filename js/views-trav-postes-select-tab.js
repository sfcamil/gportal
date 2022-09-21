

/**
 * Javascript Drupal Bootstrap File
 */
(function($, Drupal, window, document, undefined) {
	Drupal.behaviors.selectTravTab = {
		attach: function(context) {
			// http://gportal9/trav-details/30325588700#lb-tabs-tabs-3

			jQuery('.views-field').click(function() { // bind click event to link
				jQuery("#tabs").tabs({ active: 2 });
				return false;
			});

			$('.views-field').on('click', function(e) {
				jQuery("#tabs").tabs({ active: 2 });
				return false;
			});
		}
	}
})(jQuery, Drupal, this, this.document);



