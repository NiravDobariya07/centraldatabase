@extends('layout.master')

@section('page-title', config('app.name') . ' - Home')


@section('custom-page-style')
  <style>
      #leadsReportTable .table-wrapper {
          max-height: 300px;  /* Set max height */
          overflow-y: auto;   /* Enable vertical scrolling */
          overflow-x: auto;   /* Enable horizontal scrolling */
          position: relative;
      }

      #leadsReportTable .table thead {
          position: sticky;
          top: 0;
          background: white;  /* Ensure header is visible */
          z-index: 2;         /* Keep it above scrolling content */
      }
  </style>
@endsection

@section('page-content')
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
      <div class="row">
        <!-- Order Statistics -->
        <div class="col-12 order-0 mb-4">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between pb-0">
              <div class="card-title mb-0">
                <h5 class="fs-3 fw-bolder card-title text-primary">
                    Leads Report
                    <span id="column-customisation-settings" class="btn btn-light setting-off px-2">
                      (<span id="all-time-total-lead-count" class="text-primary fw-medium me-1">Loading...</span> Total Leads)
                    </span>
                </h5>
              </div>
            </div>
            <div class="card-body mt-3">
              <div class="row">
                <div class="col-12 col-lg-6">

                  <div class="d-flex justify-content-center mb-3">
                    <div class="d-flex align-items-center">
                        <span class="fs-3 fw-bold me-2 text-primary" id="selected-period-total-lead-count"></span>
                        <span class="fs-5 text-muted" id="leads-label"></span>
                    </div>
                  </div>

                  <div class="d-flex justify-content-center align-items-center mb-3">
                  <canvas id="leadsReportChart"></canvas>
                  <span class="text-secondary fw-semibold mt-5" id="NoDataMessage" style="display: none;"></span>
                  </div>
                </div>
                <div class="col-12 col-lg-6">
                  <div class="row">
                    <div class="col-12">
                      <div class="row d-flex justify-content-start">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <div class="d-flex align-items-center">
                              <span class="fs-3 fw-bold me-2 text-primary" id="selected-period-total-lead-count"></span>
                              <span class="fs-5 text-muted" id="leads-label"></span>
                          </div>
                        </div>

                        <div class="col-4 col-xxl-3 col-xl-4 d-flex flex-column">
                          <div>
                            <label for="filterSelect" class="col-form-label">Select Period</label>
                            <div>
                              <select id="filterSelect" class="form-select">
                                <option value="daily">Daily</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                              </select>
                            </div>
                          </div>
                        </div>

                        <div class="col-4 col-xxl-4 col-xl-5 d-flex flex-column">
                          <div class="row mt-auto" id="dateInput">
                            <label for="html5-date-input" class="col-form-label">Date</label>
                            <div>
                              <input class="form-control" type="date" id="html5-date-input">
                            </div>
                          </div>

                          <div class="row" id="monthInput" style="display: none;">
                            <label for="html5-month-input" class="col-form-label">Month</label>
                            <div>
                              <input class="form-control" type="month" id="html5-month-input">
                            </div>
                          </div>

                          <div class="row" id="yearInput" style="display: none;">
                            <label for="html5-year-input" class="col-form-label">Year</label>
                            <div>
                              <select class="form-control" id="html5-year-input"></select>
                            </div>
                          </div>
                        </div>

                        <div class="col-4 col-xxl-3 col-xl-4 d-flex flex-column mt-3">
                          <div class="mt-auto">
                            <button class="btn btn-primary w-100" id="refresh-leads-report">Refresh</button>
                          </div>
                        </div>
                      </div>

                      <div class="col-12" style="max-height: 600px; overflow-y: auto;">
                        <!-- Table Structure -->
                        <div class="card">
                            <h5 class="card-header"></h5>
                            <div class="table-responsive px-2">
                                <table class="table table-bordered" id="leadsReportTable">
                                    <thead>
                                      <tr>
                                        <th class="fs-6">List Id</th>
                                        <th class="fs-6">No. Of Leads</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--/ Basic Bootstrap Table -->
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </div>
        <!--/ Order Statistics -->
      </div>
    </div>
    <!-- / Content -->

    <!-- Footer -->
    @include('layout.footer')
    <!-- / Footer -->

    <div class="content-backdrop fade"></div>
  </div>
@endsection

@section('custom-page-scripts')
<script src="{{ asset('js/dashboards-analytics.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/chart.js') }}?v={{ currentVersion() }}"></script>
<script>
 $(document).ready(function () {
    $('.menu-item').removeClass('active'); 
    $('.menu-item-home').addClass('active');

    let statisticsChart = null; // Store chart instance

    function renderLeadsReportChart(labels = [], series = []) {
      const chartElement = document.getElementById("leadsReportChart");

      if (!chartElement) return; // Ensure the element exists

      // ✅ Properly destroy previous chart instance if it exists
      if (statisticsChart) {
          statisticsChart.destroy();
          statisticsChart = null; // Clear reference
      }

      // Ensure the canvas fills its parent
      chartElement.style.width = "100%";
      chartElement.style.height = "500px";


      // Get context
      const ctx = chartElement.getContext("2d");


      // Custom Plugin for Center Text
      // const centerTextPlugin = {
      //     id: "centerText",
      //     beforeDraw: function (chart) {
      //         const { width, height } = chart;
      //         const ctx = chart.ctx;

      //         ctx.restore();
      //         const fontSize = (height / 10).toFixed(2);
      //         ctx.font = `${fontSize}px Arial`;
      //         ctx.textBaseline = "middle";
      //         ctx.fillStyle = "#333";

      //         const text = 100;
      //         const textX = Math.round((width - ctx.measureText(text).width) / 2);
      //         const textY = height / 2;

      //         ctx.fillText(text, textX, textY);
      //         ctx.save();
      //     }
      // };

      // Chart configuration
      statisticsChart = new Chart(ctx, {
          type: "doughnut",
          data: {
              labels: labels, // Dynamic Labels
              datasets: [{
                  data: series, // Chart Data
                  backgroundColor: [
                      "#696cff", // Primary
                      "#8592a3", // Secondary
                      "#03c3ec", // Info
                      "#71dd37", // Success
                      "#ffab00", // Warning
                      "#ff4c4c", // Danger
                      "#333333"  // Dark
                  ],
                  borderWidth: 2,
                  borderColor: "#fff"
              }]
          },
          options: {
            responsive: true,
              maintainAspectRatio: false,
              aspectRatio: 1,
              cutout: "65%",
              layout: {
                  padding: 10
              },
              plugins: {
                  legend: {
                      display: true,
                      position: "right", // Move legend to the right side
                      labels: {
                          boxWidth: 28, // Smaller legend boxes
                          padding: 10
                      }
                  },
                  tooltip: {
                      enabled: true,
                      callbacks: {
                          label: function (tooltipItem) {
                              return tooltipItem.raw.toFixed(0); // Remove decimal
                          }
                      }
                  }
              }
          },
          // plugins: [centerTextPlugin]
      });
    }

    // Ensure DataTable is initialized
    const LEADS_REPORT_TABLE = $("#leadsReportTable").DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10,
        scrollY: "300px",  // ✅ Scrollable rows
        order: [[1, "desc"]]
    });

    // ✅ Update DataTable on AJAX success
    function updateLeadsReportTable(response) {
      LEADS_REPORT_TABLE.clear(); // ✅ Clear existing data

      response.data.forEach(item => {
          LEADS_REPORT_TABLE.row.add([
              `<span class="fw-semibold">${item.list_id}</span>`, // ✅ Bootstrap class for bold text
              `<span class="fw-semibold text-primary">${item.lead_count}</span>`
          ]);
      });

      LEADS_REPORT_TABLE.draw(); // ✅ Redraw table with new data
    }

    function fetchLeadsReport() {
      const selectedFilter = $("#filterSelect").val();
      let dateValue = "";

      if (selectedFilter === "daily") {
          dateValue = $("#html5-date-input").val(); // Format: YYYY-MM-DD
      } else if (selectedFilter === "monthly") {
          dateValue = $("#html5-month-input").val(); // Format: YYYY-MM
      } else if (selectedFilter === "yearly") {
          dateValue = $("#html5-year-input").val(); // Format: YYYY
      }

      $('#preloader').show();
      $.ajax({
          url: "{{ route('leads.report') }}", // Use named route
          type: "POST",
          data: {
              _token: "{{ csrf_token() }}",
              filter: selectedFilter,
              date_value: dateValue
          },
          success: function (response) {
              $('#preloader').hide();
              const filteredTotalLeadsCount = response?.filtered_total_leads_count || 0;
              const totalLeadsCount = response?.total_leads_count || 0;

              // Format numbers with commas
              const formattedTotalLeadsCount = totalLeadsCount.toLocaleString();
              const formattedFilteredTotalLeadsCount = filteredTotalLeadsCount.toLocaleString();

              $('#all-time-total-lead-count').text(formattedTotalLeadsCount);
              $('#selected-period-total-lead-count').text(formattedFilteredTotalLeadsCount);


              const filterType = response?.filter || 'daily';
              const dateValue = response?.date_value || '';

              let labelText = "Leads";

              if (filterType === "daily") {
                  labelText = `Leads By ${moment(dateValue).format("MMMM D, YYYY")}`;
              } else if (filterType === "monthly") {
                  labelText = `Leads By ${moment(dateValue, "YYYY-MM").format("MMMM YYYY")}`;
              } else if (filterType === "yearly") {
                  labelText = `Leads By ${dateValue}`;
              }

              $('#leads-label').text(labelText);

              updateLeadsReportTable(response);

              const labels = response.data.map(item => item.list_id);
              const series = response.data.map(item => item.lead_count);

              // Generate chart with new data
              renderLeadsReportChart(labels, series);

              if (filteredTotalLeadsCount > 0) {
                $("#leadsReportChart").show();
                $("#NoDataMessage").hide();
              } else {
                $("#leadsReportChart").hide();
                $("#NoDataMessage").show();
              }

              let message = `${filteredTotalLeadsCount} ${labelText}`;
              $("#NoDataMessage").text(message);
          },
          error: function (error) {
              $('#preloader').hide();
              console.error("Error fetching data:", error);
          }
      });
    }



    const filterSelect = $("#filterSelect");
    const dateInput = $("#dateInput");
    const monthInput = $("#monthInput");
    const yearInput = $("#yearInput");
    const yearSelect = $("#html5-year-input");

    // Pass Laravel timezone to JavaScript
    const appTimezone = "{{ config('app.timezone') }}";

    // Get the current year using Moment.js in the app's timezone
    const currentYear = moment().tz(appTimezone).year();

    // Populate year dropdown from 2025 to current year
    for (let year = currentYear; year >= 2025; year--) {
        yearSelect.append($("<option>", { value: year, text: year }));
    }

    // Set default selected year
    yearSelect.val(currentYear);

    function updateInputs() {
        const selectedValue = filterSelect.val();
        const today = moment().tz(appTimezone); // Get today's date in the app timezone
        let selectedValueText = "";

        if (selectedValue === "daily") {
            dateInput.show();
            monthInput.hide();
            yearInput.hide();
            $("#html5-date-input").val(today.format("YYYY-MM-DD"));
            selectedValueText = $("#html5-date-input").val();
        } else if (selectedValue === "monthly") {
            dateInput.hide();
            monthInput.show();
            yearInput.hide();
            $("#html5-month-input").val(today.format("YYYY-MM"));
            selectedValueText = $("#html5-month-input").val();
        } else if (selectedValue === "yearly") {
            dateInput.hide();
            monthInput.hide();
            yearInput.show();
            $("#html5-year-input").val(today.format("YYYY"));
            selectedValueText = $("#html5-year-input").val();
        }
    }

    filterSelect.on("change", updateInputs);

    // Trigger API call on change
    $("#filterSelect, #html5-date-input, #html5-month-input, #html5-year-input").on("change", fetchLeadsReport);

    updateInputs(); // Initialize on load
    fetchLeadsReport();

    $(document).on('click', '#refresh-leads-report', fetchLeadsReport);
  });
</script>
@endsection