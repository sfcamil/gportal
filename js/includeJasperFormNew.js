(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.includeJasper = {
    attach: function (context, settings) {
      $(document, context).once('containerJasperReport').each(function () {
        // https://community.jaspersoft.com/wiki/best-practices-deploying-jasperreports-server-your-web-application

        // BUG
        // https://community.jaspersoft.com/wiki/chromium-80-update-february-2020-cross-site-cookie-blocking-jaspersoft

        // console.clear();
        var url = drupalSettings.gepsis.url;
        var user = drupalSettings.gepsis.user;
        var pass = drupalSettings.gepsis.pass;
        var organisation = drupalSettings.gepsis.organisation;
        var resource = drupalSettings.gepsis.resource;
        var adhCode = drupalSettings.gepsis.adhCode;
        var token = drupalSettings.gepsis.token;
        var report = null;
        var dashboard = null;
        var visualize = null;

        $('#currentPage').hide();
        $('#previousPage').hide();
        $('#nextPage').hide();
        $('#edit-actions-submit').prop('disabled', true);
        $('#edit-actions-submit').on("submit", function (e) {
          e.preventDefault();
        });

        // START TOKEN LOGIN
        visualize.config({
          server: url,
          // scripts: "optimized-scripts",
          logEnabled: true,
          logLevel: "debug",
          auth: {
            token: token,
            preAuth: true,
            tokenName: "pp"
          }
        });


        // STRT PRINT PROCESS
        $('.throbber').hide();
        populateSelectList();
        printDashBoard();


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
              $("button").prop("disabled", false);
            }
          });
        }

        // PRINT REPORT ON CLICK SELECT
        $('#edit-selectionner-un-rapport').on('change', function () {
          console.clear();
          if ($("#edit-selectionner-un-rapport").val()) {
            report = null;
            $('.throbber').show();
            visualize(function (v) {
              v.inputControls({
                resource: $('#edit-selectionner-un-rapport').val(),
                error: function (error) {
                  console.log(error.message);
                }
              });

              var monCode;
              var el1 = document.getElementById('edit-selectionner-un-rapport');
              var pos1 = el1.options[el1.selectedIndex].text.includes('Visites');
              monCode = 'code_1';
              if (pos1) {
                monCode = 'as_code_1';
              }

              // PRINT REPORT
              var currentPage = 1,
                  totalPages,
                  report = v.report({
                    resource: $('#edit-selectionner-un-rapport').val(),
                    container: '#edit-processed-rapport',
                    scale: 'container',
                    params: {monCode: [adhCode]},
                    autoresize: true,
                    defaultJiveUi: {enabled: true},
                    success: function (data) {
                      $('#edit-actions-submit').prop('disabled', false);
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
                          $('#currentPage').show();
                          $('#previousPage').show();
                          $('#nextPage').show();
                        } else {
                          $('#currentPage').hide();
                          $('#previousPage').hide();
                          $('#nextPage').hide();
                        }
                      }
                    },
                    error: function (error) {
                      $('.throbber').hide();
                      console.log(error.message);
                    }
                  });

              var el2 = document.getElementById('edit-selectionner-un-rapport');
              var pos2 = el2.options[el2.selectedIndex].text.includes('Graphique');
              if (pos2) {
                $('#edit-processed-rapport').width('100%').height('1000px');
              } else {
                $('#edit-processed-rapport').width('100%').height('100%');
              }
              report.resize();

              // PAGES
              $('#currentPage').on('change', function () {
                var value = parseInt($(this).val(), 10);

                if (!isNaN(value) && value >= 1 && value <= totalPages) {
                  currentPage = value;
                  report.pages(currentPage).run().done(checkPagesConditions)
                      .fail(function (error) {
                        console.log(error.message);
                      });
                }
              });

              $('#previousPage').on('click', function () {
                report
                    .pages(--currentPage)
                    .run()
                    .done(checkPagesConditions)
                    .fail(function (error) {
                      console.log(error.message);
                    });
              });

              $('#nextPage').on('click', function () {
                report
                    .pages(++currentPage)
                    .run()
                    .done(checkPagesConditions)
                    .fail(function (error) {
                      console.log(error.message);
                    });
              });

              function checkPagesConditions() {
                $('#currentPage').val(currentPage);
                $('#previousPage').prop('disabled', currentPage === 1);
                $('#nextPage').prop('disabled', currentPage === totalPages);
              }

              // EXPORT REPORT
              $('#edit-actions-submit').on('click', function () {
                if (report) {
                  report.export({
                    outputFormat: 'pdf'
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
                } else if (dashboard) {
                  dashboard.export({
                        outputFormat: "pdf"
                      },
                      function (link) {
                        console.log('link add', link);
                        var url = link.href ? link.href : link;
                        window.location.href = url;
                      },
                      function (error) {
                        console.log(error.message);
                      });
                }
              });

            });
          } else {
            $("#edit-actions-submit").prop('disabled', true);
            $("#edit-processed-rapport").html("");
            printDashBoard();
          }
        });


        // PRINT DASHBOARD
        function printDashBoard() {
          $('.throbber').show();
          visualize(function (v) {
            dashboard = v.dashboard({
              resource: resource,
              container: "#edit-processed-rapport",
              params: {"code_1": [adhCode]},
              success: function () {
                console.log("dashboard rendered");
                //rapportPrinted.val('TRUE');
                $('.throbber').hide();
              },
              error: function (error) {
                console.log(error);
                $('.throbber').hide();
              }
            });
          });
          $('.throbber').hide();

        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings));

