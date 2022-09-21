(function($, Drupal, drupalSettings) {
	'use strict';
	Drupal.behaviors.calculPeriodicite = {
		attach: function(context, settings) {
			// $('main', context).once('unsplashtest').each(function() {
			// $('[data-drupal-selector="edit-risques-risques"]').once('some-arbitrary-key').each(function() {
			// $(context).find("#edit-risques-risques").once('some-arbitrary-key').each(function() {
			// $('body', context).once('DisplayModal').on('click', '.modal-trigger', function(e) {

			console.clear();
			var age = null;
			var calcVars = new Array();
			var type = drupalSettings.gepsis.type;

			if (type == 'details') {
				var typeContrCateg = $("[name='risques_type_category_risques_declares']");
				var catCalcule = $('[data-drupal-selector="edit-risques-categorie-declaree"]');
				var periodCalcule = $('[data-drupal-selector="edit-risques-periodicite-indicative"]');
				var examTypeOidCalcule = $('[data-drupal-selector="risques_examen_type_oid"]');
				var risquesAtribuer = $('[data-drupal-selector="edit-risques-risques"]');
				var aucuneRisque = $('[data-drupal-selector="edit-risques-risques-506035200"]');
			} else if (type == 'new') {
				var typeContrCateg = $("[name='new-sal_type_category_risques_declares']");
				var catCalcule = $('[data-drupal-selector="edit-new-sal-categorie-declaree"]');
				var periodCalcule = $('[data-drupal-selector="edit-new-sal-periodicite-indicative"]');
				var examTypeOidCalcule = $('[data-drupal-selector="new-sal_examen_type_oid"]');
				var risquesAtribuer = $('[data-drupal-selector="edit-new-sal-risques"]');
				var aucuneRisque = $('[data-drupal-selector="edit-new-sal-risques-506035200"]');
			}

			var othersRisques = $("input[type='checkbox']")
				.not(aucuneRisque);

			catCalcule.attr('readonly', 'readonly');
			periodCalcule.attr('readonly', 'readonly');

			cleanResult();
			calculValues();

			function cleanResult() {
				catCalcule.val('');
				periodCalcule.val('');
				risquesAtribuer.val([]);
			}

			aucuneRisque.once('aucuneRisque').change(function() {
				if (this.checked) {
					othersRisques.prop('checked', false);
				}
				calculValues();
			});

			othersRisques.once('othersRisques').change(function() {
				if (this.checked) {
					aucuneRisque.prop('checked', false);
				}
				calculValues();
			});

			function calculValues() {
				var risques = '';
				calcVars[1] = '';
				var i = 0;

				age = drupalSettings.gepsis.age;
				calcVars[0] = drupalSettings.gepsis.contrat_type;

				cleanResult();

				// populate array with checked values
				if (aucuneRisque.is(':checked')) {
					aucuneRisque.each(function() {
						if ($(this).is(':checked'))
							risques = $(this).val();
					});
				} else {
					othersRisques.each(function() {
						if ($(this).is(':checked')) {
							if (i == 0)
								risques = $(this).val();
							else
								risques += ',' + $(this).val();
							i++;
						}

					});
				}
				calcVars[1] = risques;
				calculPeriodicite();
			}

			function calculPeriodicite() {
				if (calcVars[0].length > 0 && calcVars[1].length > 0) {

					// pour ne fas faire submit pendant le calcul de risque
					$('input.form-submit').prop('disabled', true);

					$.ajax({
						// async: false,
						type: 'POST',
						url: '/calculate_risk_pers_details',
						// url: Drupal.url('/calculate_risk_pers_details'),
						dataType: 'json',
						data: {
							typeContr: calcVars[0],
							risqContr: calcVars[1],
							age: age
						},
						success: function(data) {
							//console.clear();
							// console.table(data);
							console.log(data);
							if (data.pdcCalcule != "-1") {
								// alert(data.typeCalcule);
								catCalcule.val(data.typeCalcule);
								periodCalcule.val(data.pdcCalcule);
								examTypeOidCalcule.val(data.examTypeOid);
								typeContrCateg.val(data.typeContrCateg);
							}
						},
						error: function() {
							console.log("Error");
							catCalcule.val("Error");
							periodCalcule.val("Error");
						},
						complete: function(data) {
							// console.log("Complete");
							$('input.form-submit').prop('disabled', false);
						}
					});
				}
			}
			//})
		}
	};
}(jQuery, Drupal, drupalSettings));
