(function ($, Drupal, drupalSettings) {
    'use strict';
    Drupal.behaviors.includeJasper = {
        attach: function (context, settings) {
            $(document, context).once('containerJasperReport').each(function () {
                // https://community.jaspersoft.com/wiki/best-practices-deploying-jasperreports-server-your-web-application
                // BUG: https://community.jaspersoft.com/wiki/chromium-80-update-february-2020-cross-site-cookie-blocking-jaspersoft
                // https://community.jaspersoft.com/wiki/visualizejs-api-notes-and-samples-v56
                // http://jsfiddle.net/t51bw3je/21/
                // https://helicaltech.com/visualize-js-the-input-controls-api/

                console.clear();
                var url = drupalSettings.gepsis.url;
                var user = drupalSettings.gepsis.user;
                var pass = drupalSettings.gepsis.pass;
                var organisation = drupalSettings.gepsis.organisation;
                var resource = drupalSettings.gepsis.resource;
                var adhCode = drupalSettings.gepsis.adhCode;
                var token = drupalSettings.gepsis.token;
                var dashboard;
                var report;

                hideNextPrev();

                $('#edit-actions-submit-export').prop('disabled', true);
                $('#edit-actions-submit-export').on("submit", function (e) {
                    e.preventDefault();
                });

                $("#edit-actions-submit-export-format").prop('disabled', true);

                // START TOKEN LOGIN
                visualize.config({
                    server: url,
                    scripts: "optimized-scripts",
                    logEnabled: false,
                    logLevel: "debug",
                    auth: {
                        token: token,
                        preAuth: true,
                        tokenName: "pp"
                    }
                });


                // START PRINT PROCESS
                $('.throbber').hide();
                populateSelectList();
                printDashBoard();

                function hideNextPrev() {
                    $('#previousPage').hide();
                    $('#currentPage').hide();
                    $('#nextPage').hide();
                }

                function showNextPrev() {
                    $('#previousPage').show();
                    $('#currentPage').show();
                    $('#nextPage').show();
                }


                // POPULATE SELECT RAPPORT LIST
                function populateSelectList() {
                    visualize(function (v) {
                        v.resourcesSearch({
                            folderUri: "/OCARA/PORTAIL/Rapports",
                            recursive: true,
                            types: ["reportUnit"],
                            success: renderResults,
                            error: function (error) {
                                console.log(error.message);
                            }
                        });

                        function renderResults(results) {
                            var option = "<option value=''>- Aucun(e) -</option>";
                            for (var i = 0; i < results.length; i++) {
                                option += "<option value='" + results[i].uri + "'>" + results[i].label + "</option>";
                            }
                            $("#edit-selectionner-un-rapport").html(option);
                            $("#edit-selectionner-un-rapport").prop("disabled", false);
                        }
                    });
                }

                // END POPULATE SELECT RAPPORT LIST

                // POPULATE SELECT EXPORT FORMAT LIST
                function populateExportSelectList() {
                    visualize(function (v) {
                        v.exportFormats({
                            folderUri: "/OCARA/PORTAIL/Rapports",
                            recursive: true,
                            types: ["reportUnit"],
                            success: renderResultsFormat,
                            error: function (error) {
                                console.log(error.message);
                            }
                        });

                        function renderResultsFormat(results) {
                            var option = "<option value=''>- Aucun(e) -</option>";
                            for (var i = 0; i < results.length; i++) {
                                option += "<option value='" + results[i].uri + "'>" + results[i].label + "</option>";
                            }
                            $("#edit-actions-submit-export-format").html(option);
                            $("#edit-actions-submit-export-format").prop("disabled", false);
                        }
                    });
                }

                // END POPULATE SELECT EXPORT FORMAT LIST

                // GET INPUT LIST
                function getInputList() {
                    visualize(function (v) {
                        v.inputControls({
                            folderUri: "/OCARA/PORTAIL/Rapports",
                            recursive: true,
                            types: ["reportUnit"],
                            success: renderResults,
                            error: function (error) {
                                console.log(error.message);
                            }
                        });

                        function renderResults(results) {
                            var option = "<option value=''>- Aucun(e) -</option>";
                            for (var i = 0; i < results.length; i++) {
                                option += "<option value='" + results[i].uri + "'>" + results[i].label + "</option>";
                            }
                            $("#edit-selectionner-un-rapport").html(option);
                            $("#edit-selectionner-un-rapport").prop("disabled", false);
                        }
                    });
                }

                // END GET INPUT LIST

                // LOAD AND SHOW REPORT
                function loadReport(uri) {
                    visualize(function (v) {
                        var monCode = null;
                        uri.includes('Visites')?monCode = "as_code_1":monCode = "code_1";

                        // GET AND SHOW REPORT
                        var currentPage = 1,
                            totalPages,
                            report = v.report({
                                resource: uri,
                                container: "#edit-processed-rapport-container",
                                scale: "container",
                                params: {[monCode] : [adhCode]},
                                autoresize: true,
                                // runImmediately: false,
                                defaultJiveUi: {enabled: true},
                                success: function (data) {
                                    $("#edit-actions-submit-export").prop('disabled', false);
                                    $("#edit-actions-submit-export-format").prop('disabled', false);
                                },
                                events: {
                                    reportCompleted: function () {
                                        $('.throbber').hide();
                                    },
                                    changePagesState: function (page) {
                                        currentPage = page;
                                        checkPagesConditions();
                                    },
                                    changeTotalPages: function (pages) {
                                        totalPages = pages;
                                        checkPagesConditions();
                                        if (totalPages > 1) {
                                            showNextPrev();
                                        } else {
                                            hideNextPrev();

                                        }
                                    }
                                },
                                error: function (error) {
                                    $('.throbber').hide();
                                    console.log(error.message);
                                }
                            });

                        var e = document.getElementById("edit-selectionner-un-rapport");
                        var position = e.options[e.selectedIndex].text.includes('Graphique');
                        if (position) {
                            $("#edit-processed-rapport-container").width("100%").height("1000px");
                        } else {
                            $("#edit-processed-rapport-container").width("100%").height("100%");
                        }
                        report.resize();

                        // EXPORTA FORMATS
                        var reportExports =  v.report
                            .exportFormats
                            .concat(["json"]);
                        populateExportSelectList(reportExports),

                        // PAGES
                        $('#currentPage').on('change', function () {
                            var value = parseInt($(this).val(), 10);
                            if (!isNaN(value) && value >= 1 && value <= totalPages) {
                                currentPage = value;
                                report
                                    .pages(currentPage)
                                    .run()
                                    .done(checkPagesConditions)
                                    .fail(function (err) {
                                        alert(err);
                                    });
                            }
                        });

                        $('#previousPage').on('click', function () {
                            report
                                .pages(--currentPage)
                                .run()
                                .done(checkPagesConditions)
                                .fail(function (err) {
                                    alert(err);
                                });
                        });

                        $('#nextPage').on('click', function () {
                            report
                                .pages(++currentPage)
                                .run()
                                .done(checkPagesConditions)
                                .fail(function (err) {
                                    alert(err);
                                });
                        });

                        function checkPagesConditions() {
                            $('#currentPage').val(currentPage);
                            $('#previousPage').prop('disabled', currentPage === 1);
                            $('#nextPage').prop('disabled', currentPage === totalPages);
                        }

                        // EXPORT REPORT
                        const x = $('#edit-actions-submit-export')[0];
                        x.parentNode.replaceChild(x.cloneNode(true),x);
                        $('#edit-actions-submit-export').on('click', function () {
                            var formatType = $('#edit-actions-submit-export-format').val();
                            report.export({
                                // outputFormat: "pdf"
                                outputFormat: formatType
                            })
                                .done(function (link) {
                                    // alert(link.href);
                                    window.open(link.href);
                                    $('.throbber').hide();
                                })
                                .fail(function (error) {
                                    console.log(error.message);
                                    $('.throbber').hide();
                                });

                        });
                        // EXPORT REPORT
                    });
                }

                // END LOAD AND SHOW REPORT




                // SHOW REPORT ON CLICK SELECT
                $('#edit-selectionner-un-rapport').on('change', function () {
                    console.clear();
                    if ($("#edit-selectionner-un-rapport").val()) {
                        $('.throbber').show();
                        loadReport($("#edit-selectionner-un-rapport").val());
                        // report.resource($("#edit-selectionner-un-rapport").val()).run();
                    } else {
                        hideNextPrev();
                        $("#edit-actions-submit-export").prop('disabled', true);
                        $("#edit-processed-rapport-container").html("");
                        printDashBoard();
                    }
                });
                // END SHOW REPORT ON CLICK SELECT

                // PRINT DASHBOARD
                function printDashBoard() {
                    $('.throbber').show();
                    visualize(function (v) {
                        dashboard = v.dashboard({
                            resource: resource,
                            container: "#edit-processed-rapport-container",
                            params: {"code_1": [adhCode]},
                            success: function () {
                                console.log("dashboard rendered");
                            },
                            error: function (error) {
                                console.log(error.message);
                            }
                        });
                    });
                    $("#edit-processed-rapport-container").width("100%").height("1000px");
                    $('.throbber').hide();
                }

                // END PRINT DASHBOARD


            });
        }
    };
}(jQuery, Drupal, drupalSettings));

