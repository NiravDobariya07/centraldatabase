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
                    Dashboard
                    <span id="column-customisation-settings" class="btn btn-light setting-off px-2">
                      (<span id="all-time-total-lead-count" class="text-primary fw-medium me-1">Loading...</span> Total Records)
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
                                <option value="daily">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="total">Total Leads</option>
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
                                <table class="table table-bordered" id="dashboardCountsTable">
                                    <thead>
                                      <tr>
                                        <th class="fs-6">Listing Type</th>
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
                      "#696cff", // Primary - Leads
                      "#8592a3", // Secondary - Consumer Insite
                      "#03c3ec", // Info - TRA Lead
                      "#71dd37", // Success - WhiteCollar Lead
                      "#ffab00", // Warning - Sites Token
                      "#ff4c4c", // Danger - Blacklist List
                      "#333333"  // Dark - Ext Lead
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
                          padding: 10,
                          usePointStyle: true,
                          generateLabels: function(chart) {
                              // Ensure all labels are shown, even for very small values
                              const data = chart.data;
                              if (data.labels.length && data.datasets.length) {
                                const dataset = data.datasets[0];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => {
                                  const value = dataset.data[i] || 0;
                                  const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                  return {
                                    text: label + ': ' + value.toLocaleString() + ' (' + percentage + '%)',
                                    fillStyle: dataset.backgroundColor[i],
                                    strokeStyle: dataset.backgroundColor[i],
                                    hidden: false,
                                    index: i
                                  };
                                });
                              }
                              return [];
                          }
                      },
                      onClick: function(e, legendItem) {
                          // Prevent hiding segments when clicking legend (optional)
                          // You can remove this if you want normal legend behavior
                      }
                  },
                  tooltip: {
                      enabled: true,
                      callbacks: {
                          label: function (tooltipItem) {
                              const label = tooltipItem.label || '';
                              const value = tooltipItem.raw || 0;
                              const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                              const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                              return label + ': ' + value.toLocaleString() + ' (' + percentage + '%)';
                          }
                      }
                  }
              },
              // Ensure all segments are visible, even very small ones
              elements: {
                  arc: {
                      borderWidth: 2,
                      borderAlign: 'center'
                  }
              }
          },
          // plugins: [centerTextPlugin]
      });
    }

    // Ensure DataTable is initialized for dashboard counts
    const DASHBOARD_COUNTS_TABLE = $("#dashboardCountsTable").DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10,
        scrollY: "300px",  // ✅ Scrollable rows
        order: [[1, "desc"]]
    });

    // ✅ Update DataTable on AJAX success
    function updateDashboardCountsTable(response) {
      DASHBOARD_COUNTS_TABLE.clear(); // ✅ Clear existing data

      // Sort by count descending to show largest first, but ensure all items are included
      const sortedData = [...response.data].sort((a, b) => b.count - a.count);

      sortedData.forEach(item => {
          DASHBOARD_COUNTS_TABLE.row.add([
              `<span class="fw-semibold">${item.listing_type}</span>`, // ✅ Bootstrap class for bold text
              `<span class="fw-semibold text-primary">${item.count.toLocaleString()}</span>`
          ]);
      });

      DASHBOARD_COUNTS_TABLE.draw(); // ✅ Redraw table with new data
    }

    function fetchDashboardCounts() {
      const selectedFilter = $("#filterSelect").val();
      let dateValue = "";

      if (selectedFilter === "daily") {
          dateValue = $("#html5-date-input").val(); // Format: YYYY-MM-DD
      } else if (selectedFilter === "yesterday") {
          // Calculate yesterday's date in the app timezone
          const yesterday = moment().tz(appTimezone).subtract(1, 'days');
          dateValue = yesterday.format("YYYY-MM-DD");
      } else if (selectedFilter === "monthly") {
          dateValue = $("#html5-month-input").val(); // Format: YYYY-MM
      } else if (selectedFilter === "yearly") {
          dateValue = $("#html5-year-input").val(); // Format: YYYY
      } else if (selectedFilter === "total") {
          // No date value needed for total filter
          dateValue = "";
      }

      $('#preloader').show();
      $.ajax({
          url: "{{ route('dashboard.counts') }}", // Use named route
          type: "POST",
          data: {
              _token: "{{ csrf_token() }}",
              filter: selectedFilter,
              date_value: dateValue
          },
          success: function (response) {
              $('#preloader').hide();

              // Calculate total filtered count
              const filteredTotalCount = Object.values(response.filtered_counts || {}).reduce((sum, count) => sum + count, 0);
              const totalCount = Object.values(response.total_counts || {}).reduce((sum, count) => sum + count, 0);

              // Format numbers with commas
              const formattedTotalCount = totalCount.toLocaleString();
              const formattedFilteredTotalCount = filteredTotalCount.toLocaleString();

              $('#all-time-total-lead-count').text(formattedTotalCount);
              $('#selected-period-total-lead-count').text(formattedFilteredTotalCount);

              const filterType = response?.filter || 'daily'; // Keep 'daily' as internal value for today
              const dateValue = response?.date_value || '';

              let labelText = "All Listings";

              if (filterType === "daily") {
                  labelText = `All Listings By ${moment(dateValue).format("MMMM D, YYYY")} (Today)`;
              } else if (filterType === "yesterday") {
                  labelText = `All Listings By ${moment(dateValue).format("MMMM D, YYYY")} (Yesterday)`;
              } else if (filterType === "monthly") {
                  labelText = `All Listings By ${moment(dateValue, "YYYY-MM").format("MMMM YYYY")}`;
              } else if (filterType === "yearly") {
                  labelText = `All Listings By ${dateValue}`;
              } else if (filterType === "total") {
                  labelText = "All Listings (Total)";
              }

              $('#leads-label').text(labelText);

              updateDashboardCountsTable(response);

              // Ensure all data is included, even with zero or very small values
              // Keep original order to match color scheme
              const labels = response.data.map(item => item.listing_type);
              const series = response.data.map(item => item.count);

              // Debug: Log to ensure all data is present
              console.log('Chart Labels:', labels);
              console.log('Chart Series:', series);
              console.log('Total items:', labels.length, 'Total values:', series.reduce((a, b) => a + b, 0));

              // Generate chart with new data
              renderLeadsReportChart(labels, series);

              if (filteredTotalCount > 0) {
                $("#leadsReportChart").show();
                $("#NoDataMessage").hide();
              } else {
                $("#leadsReportChart").hide();
                $("#NoDataMessage").show();
              }

              let message = `${formattedFilteredTotalCount} ${labelText}`;
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

    // Fetch earliest year from when leads began
    $.ajax({
        url: "{{ route('dashboard.earliest-year') }}",
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.earliest_year) {
                const earliestYear = parseInt(response.earliest_year);

                // Clear existing options
                yearSelect.empty();

                // Populate year dropdown from current year down to earliest year
                for (let year = currentYear; year >= earliestYear; year--) {
                    yearSelect.append($("<option>", { value: year, text: year }));
                }

                // Set default selected year
                yearSelect.val(currentYear);
            } else {
                // Fallback: if API fails, use default (2025 to current year)
                for (let year = currentYear; year >= 2025; year--) {
                    yearSelect.append($("<option>", { value: year, text: year }));
                }
                yearSelect.val(currentYear);
            }
        },
        error: function() {
            // Fallback: if API fails, use default (2025 to current year)
            for (let year = currentYear; year >= 2025; year--) {
                yearSelect.append($("<option>", { value: year, text: year }));
            }
            yearSelect.val(currentYear);
        }
    });

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
        } else if (selectedValue === "yesterday") {
            // Hide all inputs for yesterday filter
            dateInput.hide();
            monthInput.hide();
            yearInput.hide();
            // No need to set a value as it will be calculated in fetchDashboardCounts
        } else if (selectedValue === "total") {
            // Hide all inputs for total filter
            dateInput.hide();
            monthInput.hide();
            yearInput.hide();
            // No date filtering needed for total
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
    $("#filterSelect, #html5-date-input, #html5-month-input, #html5-year-input").on("change", fetchDashboardCounts);

    updateInputs(); // Initialize on load
    fetchDashboardCounts();

    $(document).on('click', '#refresh-leads-report', fetchDashboardCounts);
  });
</script>
@endsection
