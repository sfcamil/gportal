(function($, Drupal) {

	'use strict';

	Drupal.behaviors.replaceAutocomplete = {
		attach: function(context, settings) {
			
			$('.replaceAutocomplete').click(function() {
                this.value = '';
            });


			// attach custom select handler to fields with class
			$('.replaceAutocomplete').autocomplete({
				minLength: 2, /* nombre de caractères minimaux pour lancer une recherche */
				delay: 200, /* delais après la dernière touche appuyée avant de lancer une recherche */
				scrollHeight: 320,
				select: function(event, ui) {
					var valueField = $(event.target);
					if ($(event.target).hasClass('replaceAutocomplete')) {
						var valueFieldName = event.target.name + '_value';
						if ($('input[name=' + valueFieldName + ']').length > 0) {
							valueField = $('input[name=' + valueFieldName + ']');
							console.log(valueField);
							// update the labels too
							const labels = Drupal.autocomplete.splitValues(event.target.value);
							labels.pop();
							labels.push(ui.item.label);
							event.target.value = labels.join(', ');
						}
					}
					const terms = Drupal.autocomplete.splitValues(valueField.val());
					// Remove the current input.
					terms.pop();
					// Add the selected item.
					terms.push(ui.item.value);

					valueField.val(terms.join(', '));
					// Return false to tell jQuery UI that we've filled in the value already.
					return false;
				}
			});
		}
	};


})(jQuery, Drupal);