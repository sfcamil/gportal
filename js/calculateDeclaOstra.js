(function($) {
    Drupal.behaviors.calculateAdhesion = {
        attach: function(context, settings) {
            $('input').keypress(function(event) {
                return event.keyCode == 13 ? false : true;
            });

            var intFomat = 'de-DE'; // de-DE fr-FR
            var TVA = 20;

            // form fields
            var nbEff = $('#edit-effectif-eff-hors-contrat-ccda-decla-xxxx');
            var nbApr = $('#edit-total-apr-collecte-decla-xxxx');


            var mntEffHtva = $('#edit-montant-eff-htva-decla-xxxx');
            var mntAprHtva = $('#edit-montant-apr-htva-decla-xxxx');
            var mntTotHtva = $('#edit-montant-total-hors-tva-decla-xxxx');
            var mntTva = $('#edit-montant-tva-decla-decla-xxxx');
            var mntTotal = $('#edit-montant-total-de-la-facture-decla-xxxx');

            mntEffHtva.attr('readonly', 'readonly');
            mntAprHtva.attr('readonly', 'readonly');
            mntTotHtva.attr('readonly', 'readonly');
            mntTva.attr('readonly', 'readonly');
            mntTotal.attr('readonly', 'readonly');

            var tarifEffHorsContr = parseFloat($('#edit-tarif-unitaire-hors-tva-toteff-decla-xxxx').val());
            var tarifApprenti = parseFloat($('#edit-tarif-unitaire-hors-tva-totapr-decla-xxxx').val());

            recalculateTotal();

            // clear on click
            nbEff.click(function() {
                nbEff.val('');
            });

            nbApr.click(function() {
                nbApr.val('');
            });

            // format number

            nbEff.on('keyup', function() {
                var n = parseInt($(this).val().replace(/\./g, ''));
                // var n = parseInt($(this).val());
                if (n) {
                    $(this).val(
                        new Intl.NumberFormat(intFomat).format(n));
                } else {
                    $(this).val('0');
                    recalculateTotal();
                }
                // myFunc(); //call another function too
            });

            nbApr.on('keyup', function() {
                var n = parseInt($(this).val().replace(/\./g, ''));
                // var n = parseInt($(this).val());
                if (n) {
                    $(this).val(
                        new Intl.NumberFormat(intFomat).format(n));
                } else {
                    $(this).val('0');
                    recalculateTotal();
                }
                // myFunc(); //call another function too
            });

            toHtmlNumericInput("edit-tarif-unitaire-hors-tva-toteff-decla-xxxx", true);
            toHtmlNumericInput("edit-tarif-unitaire-hors-tva-totapr-decla-xxxx", true);


            if (!String.prototype.endsWith) {
                String.prototype.endsWith = function(suffix) {
                    return this.indexOf(suffix, this.length - suffix.length) !== -1;
                };
            }

            // call this function with the id of the input textbox you want to
            // be html-numeric-input
            // by default, decimal separator is '.', you can force to use comma
            // with the second parameter = true
            function toHtmlNumericInput(inputElementId,
                                        useCommaAsDecimalSeparator) {
                var textbox = document.getElementById(inputElementId);
                // called when key is pressed
                // in keydown, we get the keyCode
                // in keyup, we get the input.value (including the charactor
                // we've just typed
                var f1 = function _OnNumericInputKeyDown(e) {
                    // alert('_OnNumericInputKeyDown');
                    var key = e.which || e.keyCode; // http://keycode.info/
                    if (/* !e.shiftKey && */!e.altKey && !e.ctrlKey &&
                        // alphabet
                        key >= 65 && key <= 90 ||
                        // spacebar
                        key == 32) {
                        e.preventDefault();
                        return false;
                    }
                    if (!e.shiftKey && !e.altKey && !e.ctrlKey &&
                        // numbers
                        key >= 48 && key <= 57 ||
                        // Numeric keypad
                        key >= 96 && key <= 105 ||
                        // allow: Ctrl+A
                        (e.keyCode == 65 && e.ctrlKey === true) ||
                        // allow: Ctrl+C
                        (key == 67 && e.ctrlKey === true) ||
                        // Allow: Ctrl+X
                        (key == 88 && e.ctrlKey === true) ||
                        // allow: home, end, left, right
                        (key >= 35 && key <= 39) ||
                        // Backspace and Tab and Enter
                        key == 8 || key == 9 || key == 13 ||
                        // Del and Ins
                        key == 46 || key == 45) {
                        return true;
                    }
                    var v = this.value; // v can be null, in case textbox is
                    // number and does not valid
                    // if minus, dash
                    if (key == 109 || key == 189) {
                        // if already has -, ignore the new one
                        if (v[0] === '-') {
                            // console.log('return, already has - in the
                            // beginning');
                            return false;
                        }
                    }
                    if (!e.shiftKey && !e.altKey && !e.ctrlKey &&
                        // comma, period and numpad.dot
                        key == 190 || key == 188 || key == 110) {
                        // console.log('already having comma, period, dot',
                        // key);
                        if (/[\.,]/.test(v)) {
                            // console.log('return, already has , . somewhere');
                            return false;
                        }
                    }
                };
                var f2 = function _OnNumericInputKeyUp(e) {
                    // alert('_OnNumericInputKeyUp');
                    var v = this.value;
                    if (false) {
                        // if (+v) {
                        // this condition check if convert to number success,
                        // let it be
                        // put this condition will have better performance
                        // but I haven't test it with cultureInfo = comma
                        // decimal separator, so, to support both . and , as
                        // decimalSeparator, I remove this condition
                        // "1000" "10.9" "1,000.9" "011" "10c" "$10"
                        // +str, str*1, str-0 1000 10.9 NaN 11 NaN NaN
                    } else if (v) {
                        // refine the value

                        // this replace also remove the -, we add it again if
                        // needed

                        // ***
                        v = v.replace(/[\.]/g, '');
                        // ***
                        //
                        v = (v[0] === '-' ? '-' : '')
                            + (useCommaAsDecimalSeparator ? v.replace(
                                /[^0-9\,]/g, '') : v.replace(
                                /[^0-9\.]/g, ''));

                        // remove all decimalSeparator that have other
                        // decimalSeparator following. After this processing,
                        // only the last decimalSeparator is kept.
                        if (useCommaAsDecimalSeparator) {
                            v = v.replace(/,(?=(.*),)+/g, '');
                        } else {
                            v = v.replace(/\.(?=(.*)\.)+/g, '');
                        }
                        // console.log(this.value, v);

                        if (v.endsWith(',')) {
                            var nf = new Intl.NumberFormat(format);
                            var vx = parseFloat(v + '0');
                            v = nf.format(vx) + ',';
                        } else {
                            var idx = v.indexOf(',');
                            var nf;
                            if (idx < 0)
                                nf = new Intl.NumberFormat(format);
                            else {
                                var nrDigs = v.length - idx - 1;
                                if (nrDigs > 2) {
                                    v = v.substring(0, v.length - (nrDigs - 2));
                                    nrDigs = 2;
                                }
                                nf = new Intl.NumberFormat(format, {
                                    minimumFractionDigits: nrDigs,
                                    maximumFractionDigits: nrDigs
                                });
                            }
                            var vx = parseFloat(v.replace(/[\,]/g, '.'));
                            v = nf.format(vx);
                        }


                        // nf = new Intl.NumberFormat(format);
                        // v = nf.format(v);
                        this.value = v; // update value only if we changed it
                    }
                };

                textbox.addEventListener("keydown", f1);
                textbox.addEventListener("keyup", f2);
            }

            function recalculateTotal() {
                var nbEffVal = nbEff.val().replace(/\./g, '');
                var nbAprVal = nbApr.val().replace(/\./g, '');

                mntEffHtvaVal = nbEffVal * tarifEffHorsContr;
                mntAprHtvaVal = nbAprVal * tarifApprenti;
                mntTotHtvaVal = mntEffHtvaVal + mntAprHtvaVal;
                mntTvaVal = mntTotHtvaVal * (TVA / 100);
                mntTotalVal = mntTotHtvaVal + mntTvaVal;

                nf = new Intl.NumberFormat(intFomat, { maximumFractionDigits: 2 });

                // console.log('1) ' + nbEffVal + ' : ' + nbAprVal + ' : ' + tarifEffHorsContr);
                // console.log('2) ' + mntEffHtvaVal + ' // ' + nf.format(mntEffHtvaVal));

                mntEffHtva.val(nf.format(mntEffHtvaVal));
                mntAprHtva.val(nf.format(mntAprHtvaVal));
                mntTotHtva.val(nf.format(mntTotHtvaVal));
                mntTva.val(nf.format(roundDecimal(mntTvaVal)));
                mntTotal.val(nf.format(mntTotalVal));
            }

            function roundDecimal(nombre, precision) {
                var precision = precision || 2;
                // Pré ES2016 :  var tmp = Math.pow(10, precision);
                var tmp = 10 * precision;
                return (Math.round(nombre * tmp) / tmp);
            }

            // run recalculateTotal every time user enters a new value
            nbApr.on('input', recalculateTotal);
            nbEff.on('input', recalculateTotal);
        }
    };
}(jQuery));
// console.log(mntAprHtva.val() + ' // ' + mntEffHtva.val());
