@extends('layout.master')

@section('page-title', config('app.name') . ' - Leads Listing')

@section('custom-page-style')
    <style>
        #container-listing-column-picker .column-picker,
        #container-export-column-picker .column-picker {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        #container-listing-column-picker .column-selection-selects,
        #container-export-column-picker .column-selection-selects {
            height: 200px;
        }

        #container-listing-column-picker .actions button,
        #container-export-column-picker .actions button {
            width: 100%;
            margin: 5px 0;
        }

        #container-listing-column-picker .sortable option,
        #container-export-column-picker .sortable option {
            cursor: grab;
        }
        .modal-body .form-label{
            text-transform: none;
            font-size: 14px !important;
        }

        /* Inactive state: light gray icon */
        .setting-off i {
            color: #aaa;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        /* Active state: blue icon with rotation */
        .setting-on i {
            color: #007bff;
            transform: rotate(30deg);
            transition: color 0.3s ease, transform 0.3s ease;
        }

        #leads-table {
            table-layout: auto; /* Allows flexible column sizing */
            width: 100%; /* Ensures table uses available space */
        }

        #leads-table th, #leads-table td {
            white-space: nowrap; /* Prevents text from wrapping */
            min-width: 50px; /* Ensures columns don't shrink too much */
            max-width: 1000px; /* Prevents excessively wide columns */
            overflow: auto;
        }
    </style>
@endsection

@section('page-content')
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
      <div class="row">
        <div class="col-lg-12 mb-4 order-0">
          <div class="card">
            <div class="d-flex align-items-end" >
              <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <h5 class="fs-3 fw-bolder card-title text-primary">
                            Leads List
                            <span id="column-customisation-settings" class="btn btn-light setting-off px-1">
                                <i class="bx bx-cog display-6"></i>
                            </span>
                        </h5>
                    </div>
                    <div class="col-7">
                        <div class="d-flex justify-content-end">
                            <button id="toggle-filter-button" class="btn btn-primary me-2 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            View Filter Options
                            </button>
                            <button id="resetBtn" class="btn btn-secondary filter-reset-btn">Reset Filters</button>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="collapse" id="collapseExample" >
                        <!-- All Filter Options -->
                        <!-- Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="search_value">Search Lead Details</label>
                                <input type="text" id="search_value" class="form-control" placeholder="Search Lead Details">
                            </div>
                            <div class="col-md-6">
                                <label for="source_site_id">Filter By Source Site</label>
                                <select id="source_site_id" multiple class="form-control select2" data-placeholder="Select Source Site">
                                    @foreach($sourceSites as $site)
                                        <option value="{{ $site->id }}">{{ $site->domain }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Date Range Filters -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="mr-2">Date Subscribed</label>
                                <div class="position-relative flex-grow-1">
                                    <input type="text" id="date_subscribed_range" class="form-control" placeholder="Select Date Range">
                                    <!-- FontAwesome Cross Button -->
                                    <button type="button" id="clear_date_subscribed" class="btn btn-link position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #999;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="mr-2">Import Date</label>
                                <div class="position-relative flex-grow-1">
                                    <input type="text" id="import_date_range" class="form-control" placeholder="Select Date Range">
                                    <!-- FontAwesome Cross Button -->
                                    <button type="button" id="clear_import_date" class="btn btn-link position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #999;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- <div class="col-md-4">
                                <label>Date of Birth (DOB)</label>
                                <input type="text" id="dob_range" class="form-control" placeholder="Select Date Range">
                            </div> -->
                        </div>

                        <!-- Amount Filters -->
                        <div class="row mb-4">
                            <div class="col-12 col-xxl-6 mb-xxl-0 mb-3">
                                <div class="row" style="align-items: end;">
                                    <div class="col-3 w-auto">
                                    <label>Amount Type</label>
                                    <select id="select_amount" class="form-control">
                                    <option value="">Select Amount Type</option>
                                    <option value="tax_debt_amount">Tax Debt Amount</option>
                                    <option value="cc_debt_amount">CC Debt Amount</option>
                                </select>
                                    </div>
                                    <div class="col-3 col-md-4 w-auto tax_debt_amount">
                                        <select id="tax_debt_amount_operator" class="form-control">
                                            <option value="">Select Operator</option>
                                            <option value=">">&gt;</option>
                                            <option value="=">=</option>
                                            <option value="<">&lt;</option>
                                        </select>
                                    </div>
                                    <div class="col tax_debt_amount_field w-auto tax_debt_amount" style="display: none; width: max-content">
                                        <input type="number" id="tax_debt_amount" class="form-control" placeholder="Enter Amount">
                                    </div>

                                    <div class="col-3 col-md-4 w-auto CC_debt_amount">
                                        <select id="cc_debt_amount_operator" class="form-control">
                                            <option value="">Select Operator</option>
                                            <option value=">">&gt;</option>
                                            <option value="=">=</option>
                                            <option value="<">&lt;</option>
                                        </select>
                                    </div>
                                    <div class="col cc_debt_amount_field w-auto CC_debt_amount" style="display: none; width: max-content">
                                        <input type="number" id="cc_debt_amount" class="form-control" placeholder="Enter Amount">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xxl-6">
                                    <label for="campaign_list_id">Filter By List Id</label>
                                    <select id="campaign_list_id" class="form-control select2" multiple="multiple" data-placeholder="Select List Id">
                                        @foreach($campaignListIds as $listId)
                                            <option value="{{ $listId->id }}">{{ $listId->list_id }}</option>
                                        @endforeach
                                    </select>
                            </div>
                        </div>
                        <!-- ./Filter Section -->
                        <div class="mb-4">
                            <button id="filterBtn" class="btn btn-primary me-2">Apply Filters</button>
                            <button id="resetBtn" class="btn btn-secondary filter-reset-btn me-2">Reset Filters</button>
                            <button id="openExportModelBtn" type="button" class="btn btn-primary"> Export Leads</button>
                            <!-- <button id="exportCsv" class="btn btn-success">Export CSV</button>
                            <button id="exportExcel" class="btn btn-info">Export Excel</button>
                            <button id="exportPdf" class="btn btn-danger">Export PDF</button> -->
                        </div>
                        <!-- ./All Filter Options -->
                    </div>
                </div>

                <!-- Leads Table -->
                <table class="table table-bordered table-striped" id="leads-table" style="width: -webkit-fill-available;">
                    <thead>
                        <tr></tr>
                    </thead>
                    <tbody>
                        <tr></tr>
                    </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- / Content -->

    <!-- Extra Large Modal -->
    <div class="modal fade" id="export-leads-model" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <form id="form-export-lead-data">
                <div class="modal-content">
                    <div class="modal-header border border-bottom-4 p-3">
                        <h5 class="modal-title">Export Leads Data</h5>
                        <button id="closeExportModelBtn" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6">
                                <h6 class="mb-3">Choose What to Export:</h6>
                                <div class="row mb-4">
                                    <!-- <div class="col-6 w-auto">
                                        <div class="form-check">
                                            <input name="export_type" class="form-check-input" type="radio" value="export_all_data" id="export-all-data" checked>
                                            <label class="form-check-label" for="export-all-data"> Export All Leads </label>
                                        </div>
                                    </div> -->
                                    <div class="col-6">
                                        <div class="form-check w-auto">
                                            <input name="export_type" class="form-check-input" type="radio" value="export_filtered_data" id="export-filtered-data" checked>
                                            <label class="form-check-label" for="export-filtered-data"> Export Filtered Leads </label>
                                        </div>
                                    </div>
                                    <span class="error-export export_type d-none error"></span>
                                </div>

                                <h6 class="mb-3 d-none">Export Frequency:</h6>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="export-frequency" class="form-label">How often should we export?</label>
                                        <select id="export-frequency" name="frequency" class="form-select">
                                            <option value="one_time" selected>One Time</option>
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                    </div>
                                    <span class="error-export frequency d-none error"></span>
                                </div>

                                <div class="row mb-3 export-additional-options">
                                    <div class="col-auto frequency-option" id="day-of-week-container">
                                        <div class="mb-3">
                                            <label for="export-option-day-of-week" class="form-label">Select a Day (For Weekly Exports)</label>
                                            <select id="export-option-day-of-week" name="day_of_week" class="form-select">
                                                @foreach ($exportDaysOfWeek as $day)
                                                    <option value="{{ $day }}">{{ Str::title($day) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto frequency-option" id="day-of-month-container">
                                        <div class="mb-3">
                                            <label for="export-option-day-of-month" class="form-label">Select a Date (For Monthly Exports)</label>
                                            <select id="export-option-day-of-month" name="day_of_month" class="form-select">
                                                @for ($i = 1; $i <= 31; $i++)
                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-auto" id="time-container">
                                        <div class="mb-3">
                                            <label for="export-time" class="form-label">Select Export Time</label>
                                            <input name="time" class="form-control" type="time" value="12:00:00" id="export-time">
                                        </div>
                                    </div>
                                    <div class="alert alert-secondary d-none" id="export-note">
                                        <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="Note"></i>
                                        <span id="export-note-message"></span>
                                    </div>
                                </div>

                                <h6 class="mb-0">Choose Export Format(s):</h6>
                                <div class="row mb-4">
                                    <div class="col-6 w-auto">
                                        <div class="form-check mt-3">
                                            <input name="export_formats[]" class="form-check-input" type="checkbox" value="csv" id="export-format-csv">
                                            <label class="form-check-label" for="export-format-csv"> CSV (Comma-Separated Values) </label>
                                        </div>
                                    </div>
                                    <div class="col-6 w-auto">
                                        <div class="form-check mt-3">
                                            <input name="export_formats[]" class="form-check-input" type="checkbox" value="xlsx" id="export-format-xlsx">
                                            <label class="form-check-label" for="export-format-xlsx"> XLSX (Excel File) </label>
                                        </div>
                                    </div>
                                    <span class="error-export export_formats d-none error"></span>
                                </div>

                                <h6 class="mb-2 d-none">Export Details:</h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="export-title" class="form-label">Export Title (Optional)</label>
                                            <input name="title" id="export-title" class="form-control" type="text" placeholder="e.g., Weekly Sales Leads Report">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="export-description" class="form-label">Additional Notes (Optional)</label>
                                            <textarea name="description" id="export-description" class="form-control" type="text" placeholder="Any extra details for this export..." rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="file-prefix" class="form-label">File Name Prefix (Optional)</label>
                                            <input name="file_prefix" id="file-prefix" class="form-control" type="text" placeholder="e.g., sales_leads, customer_data">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check mt-3">
                                            <input name="export_in_batches" class="form-check-input" type="checkbox" value="1" id="export-in-batches">
                                            <label class="form-check-label" for="export-in-batches">
                                                Export in Multiple Files (Recommended for Large Data Exports)
                                            </label>
                                        </div>
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="Note"></i>
                                            <span id="export-in-batch-note">
                                                Export will process all records in a single file.
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <h5 class="mb-0">Choose & Reorder Columns</h5>
                                <div id="container-export-column-picker">
                                    <div class="column-picker text-center">
                                        <div class="row mb-2">
                                            <div class="col-md-5">
                                                <h6 class="text-start ms-2">Available Columns</h6>
                                                <select multiple id="available-columns" class="form-control column-selection-selects">
                                                    @foreach(config('export_fields.Lead') as $field)
                                                        <option value="{{ $field }}">{{ getLeadKeyByValue($field) }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="error-export export_columns d-none error"></span>
                                            </div>

                                            <div class="col-md-2 d-flex flex-column justify-content-start mt-5 gap-2">
                                                <button class="btn btn-primary fs-5" id="add-selected">&rarr;</button>
                                                <button class="btn btn-danger fs-5" id="remove-selected">&larr;</button>
                                                <button class="btn btn-success fs-5" id="add-all">&rArr;</button>
                                                <button class="btn btn-warning fs-5" id="remove-all">&lArr;</button>
                                            </div>

                                            <div class="col-md-5">
                                                <h6 class="text-start ms-2">Selected Columns</h6>
                                                <select multiple id="shown-columns" class="form-control sortable column-selection-selects"></select>

                                                <div class="mt-2 d-grid gap-2">
                                                    <button class="btn btn-secondary" id="move-up">↑ Move Up</button>
                                                    <button class="btn btn-secondary" id="move-down">↓ Move Down</button>
                                                    <button class="btn btn-secondary" id="move-top">⇡ Move to Top</button>
                                                    <button class="btn btn-secondary" id="move-bottom">⇣ Move to Bottom</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6 w-auto">
                                                <h6 class="mb-2 ms-2 text-start">Sort By:</h6>
                                                <select class="form-control" value="import_date" name="sort_by_field_name">
                                                    @foreach(config('export_fields.Lead') as $field)
                                                        <option value="{{ $field }}" {{ ($field == 'import_date') ? "selected" : "" }} >{{ $field }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 w-auto">
                                                <h6 class="mb-2 ms-2 text-start">Sort Order:</h6>
                                                <select class="form-control" name="sort_by_field_order">
                                                    <option value="asc">Ascinding Order</option>
                                                    <option value="desc" selected>Descending Order</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- <button id="apply-columns" class="btn btn-primary mt-3">Apply Selection</button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border border-top-2 p-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btn-start-lead-export">Start Export</button>
                    </div>
                </div>
            <form>
        </div>
    </div>
    <!-- ./Extra Large Modal -->

    <!-- Extra Large Listing column Selection Modal -->
    <div class="modal fade" id="leads-listing-column-selection-model" tabindex="-1">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title">Lead Listing – Column Customization</h5>
                    <button type="button" class="btn-close" id="btn-close-leads-listing-column-selection-model"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-0">Choose & Reorder Columns</h5>
                            <div id="container-listing-column-picker">
                                <div class="column-picker text-center">
                                    <div class="row mb-2">
                                        <div class="col-md-5">
                                            <h6 class="text-start ms-2">Available Columns</h6>
                                            <select multiple id="available-columns" class="form-control column-selection-selects">
                                            </select>
                                        </div>

                                        <div class="col-md-2 d-flex flex-column justify-content-start mt-5 gap-2">
                                            <button class="btn btn-primary fs-5" id="add-selected">&rarr;</button>
                                            <button class="btn btn-danger fs-5" id="remove-selected">&larr;</button>
                                            <button class="btn btn-success fs-5" id="add-all">&rArr;</button>
                                            <button class="btn btn-warning fs-5" id="remove-all">&lArr;</button>
                                        </div>

                                        <div class="col-md-5">
                                            <h6 class="text-start ms-2">Selected Columns</h6>
                                            <select multiple id="shown-columns" class="form-control sortable column-selection-selects">
                                            </select>

                                            <div class="mt-2 d-grid gap-2">
                                                <button class="btn btn-secondary" id="move-up">↑ Move Up</button>
                                                <button class="btn btn-secondary" id="move-down">↓ Move Down</button>
                                                <button class="btn btn-secondary" id="move-top">⇡ Move to Top</button>
                                                <button class="btn btn-secondary" id="move-bottom">⇣ Move to Bottom</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border border-top-2 p-3">
                    <button type="button" class="btn btn-primary" id="customFielsSaveBtn">Update</button>
                    <button type="button" class="btn btn-secondary" id="resetCustomFielsSaveBtn">Reset</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ./Extra Large Listing column Selection Modal -->

    <!-- Footer -->
    @include('layout.footer')
    <!-- / Footer -->

    <div class="content-backdrop fade"></div>
  </div>
@endsection

@section('custom-page-scripts')
<script src="{{ asset('vendor/js/jquery-3.6.0.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery.dataTables.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/dataTables.bootstrap5.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/moment.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/moment-timezone-with-data.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/daterangepicker.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/select2.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery-ui.min.js') }}?v={{ currentVersion() }}"></script>
<script>
    const appTimezone = @json(config('app.timezone'));

    $(document).ready(function () {
        var defaultFields = @json($defaultFields);
        var selectedFields = @json($selectedFields);

        $('.menu-item').removeClass('active');
        $('.menu-item-leads').addClass('active');

        function updateAvailableColumns() {
            let $select = $('#container-listing-column-picker #available-columns');
            $select.empty(); // Clear existing options

            $.each(defaultFields, function(key, label) {
                if (!selectedFields.includes(key)) {
                    $select.append($('<option>', {
                        value: key, // Field key as value
                        text: label // Field label as text
                    }));
                }
            });
        }

        function updateShownColumns() {
            let $select = $('#container-listing-column-picker #shown-columns');
            $select.empty(); // Clear existing options

            $.each(selectedFields, function(index, field) {
                $select.append($('<option>', {
                    value: field, // Ensure `key` exists
                    text: defaultFields[field] // Ensure `label` exists
                }));
            });
        }

        updateAvailableColumns();
        updateShownColumns();

        $("#tax_debt_amount_operator").change(function() {
            if ($(this).val() === "") {
                $('#tax_debt_amount').val("");
                $('.tax_debt_amount_field').hide();
            } else {
                $('.tax_debt_amount_field').show();
            }
        });

        $("#cc_debt_amount_operator").change(function() {
            if ($(this).val() === "") {
                $('#cc_debt_amount').val("");
                $('.cc_debt_amount_field').hide();
            } else {
                $('.cc_debt_amount_field').show();
            }
        });

        // Initialize Date Range Picker for import and subscribed date fields
        $('#date_subscribed_range, #import_date_range').daterangepicker({
            autoUpdateInput: false,
            showDropdowns: true,
            minDate: moment().tz(appTimezone).subtract(100, 'years'), // Allow up to 100 years back
            locale: {
                cancelLabel: 'Clear'
            },
            ranges: {
                'Today': [moment().tz(appTimezone).startOf('day'), moment().tz(appTimezone).endOf('day')],
                'Yesterday': [moment().tz(appTimezone).subtract(1, 'days').startOf('day'), moment().tz(appTimezone).subtract(1, 'days').endOf('day')],
                'Last 7 Days': [moment().tz(appTimezone).subtract(6, 'days').startOf('day'), moment().tz(appTimezone).endOf('day')],
                'Last 30 Days': [moment().tz(appTimezone).subtract(29, 'days').startOf('day'), moment().tz(appTimezone).endOf('day')],
                'This Month': [moment().tz(appTimezone).startOf('month'), moment().tz(appTimezone).endOf('month')],
                'Last Month': [moment().tz(appTimezone).subtract(1, 'month').startOf('month'), moment().tz(appTimezone).subtract(1, 'month').endOf('month')],
                'Last 3 Months': [moment().tz(appTimezone).subtract(3, 'months').startOf('month'), moment().tz(appTimezone).endOf('day')],
                'Last 6 Months': [moment().tz(appTimezone).subtract(6, 'months').startOf('month'), moment().tz(appTimezone).endOf('day')],
                'This Year': [moment().tz(appTimezone).startOf('year'), moment().tz(appTimezone).endOf('year')],
            }
        });

        setInitialImportDateRangeValue = () => {
            $('#import_date_range').val('');

            // // Set default value to "Today" only for import_date_range
            // let today = moment().tz(appTimezone);

            // $('#import_date_range').val(today.format('YYYY-MM-DD') + ' - ' + today.format('YYYY-MM-DD'));
            // $('#import_date_range').data('daterangepicker').setStartDate(today);
            // $('#import_date_range').data('daterangepicker').setEndDate(today);
        }

        setInitialImportDateRangeValue();

        $(document).on('click', '#clear_import_date', function() {
            setInitialImportDateRangeValue();
        });

        $(document).on('click', '#clear_date_subscribed', function() {
            $('#date_subscribed_range').val('');
        });

        // Initialize DOB field separately (without predefined ranges)
        // $('#dob_range').daterangepicker({
        //     autoUpdateInput: false,
        //     showDropdowns: true,
        //     minDate: moment().subtract(100, 'years'), // Allow up to 100 years back
        //     locale: {
        //         cancelLabel: 'Clear'
        //     }
        // });

        // Set selected date range on change
        $('#date_subscribed_range, #import_date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        // Clear input on cancel
        $('#date_subscribed_range, #dob_range').on('cancel.daterangepicker', function() {
            $(this).val('');
        });

        $('#import_date_range').on('cancel.daterangepicker', function() {
            setInitialImportDateRangeValue();
        });

        var defaultListingFields = [
            {
                data: 'id',
                name: 'id',
                title: 'ID',
                visible: false
            },
            {
                data: 'full_name',
                name: 'full_name',
                title: 'Name',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'email',
                name: 'email',
                title: 'Email Address',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'source_site',
                name: 'source_site',
                title: 'Source Site',
                orderable: false,
                render: function(data) {
                    return (data && data.domain) ? data.domain : 'N/A';
                }
            },
            {
                data: 'lead_id',
                name: 'lead_id',
                title: 'Lead ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'ef_id',
                name: 'ef_id',
                title: 'EF ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'ck_id',
                name: 'ck_id',
                title: 'CK ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'import_date',
                name: 'import_date',
                title: 'Import Date',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            }
        ];

        var allFields = {
            "full_name": {
                data: 'full_name',
                name: 'full_name',
                title: 'Name',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "email": {
                data: 'email',
                name: 'email',
                title: 'Email',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "phone": {
                data: 'phone',
                name: 'phone',
                title: 'Phone',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "alt_phone": {
                data: 'alt_phone',
                name: 'alt_phone',
                title: 'Alternate Phone',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "address": {
                data: 'address',
                name: 'address',
                title: 'Address',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "city": {
                data: 'city',
                name: 'city',
                title: 'City',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "state": {
                data: 'state',
                name: 'state',
                title: 'State',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "postal": {
                data: 'postal',
                name: 'postal',
                title: 'Postal Code',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "country": {
                data: 'country',
                name: 'country',
                title: 'Country',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "ip": {
                data: 'ip',
                name: 'ip',
                title: 'IP Address',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "date_subscribed": {
                data: 'date_subscribed',
                name: 'date_subscribed',
                title: 'Date Subscribed',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            "gender": {
                data: 'gender',
                name: 'gender',
                title: 'Gender',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "offer_url": {
                data: 'offer_url',
                name: 'offer_url',
                title: 'Offer URL',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "dob": {
                data: 'dob',
                name: 'dob',
                title: 'Date of Birth',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "tax_debt_amount": {
                data: 'tax_debt_amount',
                name: 'tax_debt_amount',
                title: 'Tax Debt Amount',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "cc_debt_amount": {
                data: 'cc_debt_amount',
                name: 'cc_debt_amount',
                title: 'Credit Card Debt Amount',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "type_of_debt": {
                data: 'type_of_debt',
                name: 'type_of_debt',
                title: 'Type of Debt',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "home_owner": {
                data: 'home_owner',
                name: 'home_owner',
                title: 'Home Owner',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "list_id": {
                data: 'list_id',
                name: 'list_id',
                title: 'List ID',
                orderable: false,
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "import_date": {
                data: 'import_date',
                name: 'import_date',
                title: 'Import Date',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('DD MMM YYYY, hh:mm A');
                }
            },
            "jornaya_id": {
                data: 'jornaya_id',
                name: 'jornaya_id',
                title: 'Jornaya ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "phone_type": {
                data: 'phone_type',
                name: 'phone_type',
                title: 'Phone Type',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "trusted_form_id": {
                data: 'trusted_form_id',
                name: 'trusted_form_id',
                title: 'Trusted Form ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "opt_in": {
                data: 'opt_in',
                name: 'opt_in',
                title: 'Opt-in',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "sub_id_1": {
                data: 'sub_id_1',
                name: 'sub_id_1',
                title: 'Sub ID 1',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "sub_id_2": {
                data: 'sub_id_2',
                name: 'sub_id_2',
                title: 'Sub ID 2',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "sub_id_3": {
                data: 'sub_id_3',
                name: 'sub_id_3',
                title: 'Sub ID 3',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "sub_id_4": {
                data: 'sub_id_4',
                name: 'sub_id_4',
                title: 'Sub ID 4',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "sub_id_5": {
                data: 'sub_id_5',
                name: 'sub_id_5',
                title: 'Sub ID 5',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "aff_id_1": {
                data: 'aff_id_1',
                name: 'aff_id_1',
                title: 'Affiliate ID 1',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "aff_id_2": {
                data: 'aff_id_2',
                name: 'aff_id_2',
                title: 'Affiliate ID 2',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "lead_id": {
                data: 'lead_id',
                name: 'lead_id',
                title: 'Lead ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "ef_id": {
                data: 'ef_id',
                name: 'ef_id',
                title: 'EF ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "ck_id": {
                data: 'ck_id',
                name: 'ck_id',
                title: 'CK ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "page_url": {
                data: 'page_url',
                name: 'page_url',
                title: 'Page URL',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            }
        };

        var table;

        function initializeDataTable(selectedFields = []) {
            var selectedColumns = selectedFields.length ?
                selectedFields.map(field => allFields[field] || null).filter(field => field !== null) :
                defaultListingFields;

            selectedColumns.unshift({
                data: null,
                name: 'sr_no',
                title: 'Sr. No',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    var pageInfo = $('#leads-table').DataTable().page.info();
                    return pageInfo.start + meta.row + 1; // Correct Sr. No based on pagination
                }
            });

            // Ensure the 'ID' column is always the first element
            if (selectedColumns.length && selectedColumns[0].name !== 'id') {
                selectedColumns.unshift({
                    data: 'id',
                    name: 'id',
                    title: 'ID',
                    visible: false
                });
            }

            const hasActionColumn = selectedColumns.some(col => col.data === 'action');

            if (!hasActionColumn) {
                selectedColumns.push({
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    title: 'Actions'
                });
            }

            if ($.fn.DataTable.isDataTable('#leads-table')) {
                $('#leads-table').DataTable().clear().destroy();
                $('#leads-table thead').empty();
            }

            // Add table headers dynamically
            var thead = '<tr>';
            selectedColumns.forEach(col => {
                thead += `<th>${col.title}</th>`;
            });
            thead += '</tr>';
            $('#leads-table thead').html(thead);

            // Reinitialize DataTable
            table = $('#leads-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                scrollY: "380px", // Enables vertical scrolling
                scrollX: true, // Enables horizontal scrolling for wide tables
                scrollCollapse: true, // Ensures the table fits inside the scroll container
                fixedHeader: true, // Keeps the header aligned when scrolling
                autoWidth: false, // Prevents column width distortion
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100], // Values for page length
                    [10, 25, 50, 100]  // Labels shown in dropdown
                ],
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ route('leads.index') }}",
                    data: function(d) {
                        d.search_value = $('#search_value').val() || '';
                        d.source_site_id = $('#source_site_id').val() || '';
                        d.campaign_list_id = $('#campaign_list_id').val() || '';

                        let dateSubscribed = $('#date_subscribed_range').val() ? $('#date_subscribed_range').val().split(" - ") : [];
                        d.date_subscribed_from = dateSubscribed.length ? dateSubscribed[0] : '';
                        d.date_subscribed_to = dateSubscribed.length > 1 ? dateSubscribed[1] : '';

                        let importDate = $('#import_date_range').val() ? $('#import_date_range').val().split(" - ") : [];
                        d.import_date_from = importDate.length ? importDate[0] : '';
                        d.import_date_to = importDate.length > 1 ? importDate[1] : '';

                        d.tax_debt_amount_operator = $('#tax_debt_amount_operator').val() || '';
                        d.tax_debt_amount = $('#tax_debt_amount').val() || '';
                        d.cc_debt_amount_operator = $('#cc_debt_amount_operator').val() || '';
                        d.cc_debt_amount = $('#cc_debt_amount').val() || '';
                    }
                },
                columns: selectedColumns
            });
        }

        initializeDataTable(selectedFields);

        $("#customFielsSaveBtn").click(function() {
            let selected_fields = getListingColumnOrder();

            if (selected_fields.length === 0) {
                toastr.error("Please select at least one field.");
                return;
            }

            $('#preloader').show();
            $.ajax({
                url: "{{ route('save.lead.field.setting') }}", // Backend route
                type: "POST",
                data: JSON.stringify({
                    fields: selected_fields
                }),
                contentType: "application/json",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#preloader').hide();
                    selectedFields = selected_fields;
                    toastr.success("Fields setting saved successfully!");
                    setTimeout(() => {
                        hideLeadsListingColumnSelectionModal();
                        initializeDataTable(selectedFields); // Reinitialize table with new fields
                        updateAvailableColumns();
                        updateShownColumns();
                    }, 500);
                },
                error: function(error) {
                    $('#preloader').hide();
                    toastr.error("Error saving fields.");
                    console.log(error);
                }
            });
        });

        $("#resetCustomFielsSaveBtn").click(function() {
            $('#preloader').show();
            $.ajax({
                url: "{{ route('reset.lead.field.setting') }}", // Backend route
                type: "get",
                contentType: "application/json",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#preloader').hide();
                    toastr.success("Fields setting reset successfully!");
                    selectedFields = [];
                    setTimeout(() => {
                        hideLeadsListingColumnSelectionModal();
                        initializeDataTable(selectedFields); // Reinitialize table with new fields
                        updateAvailableColumns();
                        updateShownColumns();
                    }, 500);
                },
                error: function(error) {
                    $('#preloader').hide();
                    toastr.error("Error saving fields.");
                    console.log(error);
                }
            });
        });

        // Apply Filters
        $('#filterBtn').click(function () {
            table.ajax.reload();
        });

        // Reset Filters
        $('.filter-reset-btn').click(function () {
            $('#search_value').val('');
            $('#select_amount').val('');
            $('#source_site_id').val(null).trigger('change'); // Reset Select2
            $('#campaign_list_id').val(null).trigger('change'); // Reset Select2
            $('#date_subscribed_range, #dob_range').val('');

            // Set Import Date default value this Month
            setInitialImportDateRangeValue()


            $("#tax_debt_amount_operator, #tax_debt_amount, #cc_debt_amount_operator, #cc_debt_amount").val("")
            $('.CC_debt_amount, .tax_debt_amount').hide();
            table.order([[0, 'desc']]).draw();
        });

        // Trigger search on input change
        //  $('#search_value').on('keyup', function () {
        //     table.ajax.reload(); // Refresh table when typing in search box
        // });


        $(".content-wrapper #collapseExample").on("shown.bs.collapse", function () {
            $('#campaign_list_id').select2({
                placeholder: "Select List Id",
                allowClear: true,
                width: '100%' // Ensure full width usage
            });
            $('#source_site_id').select2({
                placeholder: "Select Source Site",
                allowClear: true,
                width: '100%' // Ensure full width usage
            });
            $("#toggle-filter-button").text("Hide Filter Options");
        });

        $(".content-wrapper #collapseExample").on("hidden.bs.collapse", function () {
            $("#toggle-filter-button").text("View Filter Options");
        });

        function updatePlaceholder(selector, placeholderText) {
            var selected = $(selector).val();
            if (!selected || selected.length === 0) {
                $(selector).next('.select2-container').find('.select2-selection__rendered')
                    .html('<span class="select2-selection__placeholder">' + placeholderText + '</span>');
            }
        }

        updatePlaceholder('#campaign_list_id', 'Select List Id');

        $('#campaign_list_id').on('change', function() {
            updatePlaceholder('#campaign_list_id', 'Select List Id');
        });

        //
        // Prevent form submission on page load
        $("#form-export-lead-data").on("submit", function (e) {
            e.preventDefault(); // Stops the form from submitting
        });

        function toggleFrequencyOptions() {
            let frequency = $("#export-frequency").val();

            $(".frequency-option").hide(); // Hide all frequency-related options first
            updateNote(""); // Reset note initially

            if (frequency === "weekly") {
                $("#day-of-week-container").show();
                updateNote("If the selected day does not occur in the current week, it will be scheduled for the next available occurrence.");
            } else if (frequency === "monthly") {
                $("#day-of-month-container").show();
                updateNote("If the selected date (e.g., 31st) does not exist in a month, the export will run on the last valid day of that month.");
            }

            if (frequency === "one_time") {
                $("#time-container").hide();
            } else {
                $("#time-container").show();
            }
        }

        function updateNote(message) {
            if (message) {
                $("#export-note-message").text(message);
                $("#export-note").removeClass("d-none");
            } else {
                $("#export-note").addClass("d-none");
            }
        }

        // Initialize Bootstrap Tooltip
        $('[data-bs-toggle="tooltip"]').tooltip();

        function resetExportForm() {
            // Reset the entire form
            $("#form-export-lead-data")[0].reset();

            // Restore Available Columns to original options
            let availableColumns = $("#available-columns");
            availableColumns.empty(); // Clear existing options

            @foreach(config('export_fields.Lead') as $field)
                availableColumns.append(new Option("{{ $field }}", "{{ $field }}"));
            @endforeach

            // Clear Selected Columns
            $("#shown-columns").empty();

            // Hide error messages
            $(".error-export").addClass("d-none").text("");
        }

        function showExportLeadsModal() {
            $('#export-leads-model').modal('show');
            // Attach event handler for frequency dropdown (use "on" to avoid rebinding)
            $("#export-frequency").off("change").on("change", toggleFrequencyOptions);
        }

        function hideExportLeadsModal() {
            $('#export-leads-model').modal('hide');
            resetExportForm()
        }

        $(document).on('click', '#openExportModelBtn', function() {
            showExportLeadsModal();
        });

        $(document).on('click', '#closeExportModelBtn', function() {
            hideExportLeadsModal();
        });

        // // Event: When modal opens, set up change event for export frequency
        // $('#export-leads-model').on('shown.bs.modal', function () {
        //     console.log("Export Leads modal opened!"); // Debugging log

        //     // Attach event handler for frequency dropdown (use "on" to avoid rebinding)
        //     $("#export-frequency").off("change").on("change", toggleFrequencyOptions);
        // });

        // Handle Day of Month selection (for months without 30/31)
        $("#export-option-day-of-month").change(function () {
            let selectedDay = parseInt($(this).val());
            if (selectedDay > 28) {
                updateNote("Note: If this day doesn’t exist in a month (e.g., 30th or 31st in February), the export will run on the last valid day of that month.");
            }
        });

        $("#day-of-week-container, #day-of-month-container, #time-container").hide()

        // Helper Functions
        function moveSelected(from, to) {
            $(from + ' option:selected').appendTo(to);
        }

        function moveAll(from, to) {
            $(from + ' option').appendTo(to);
        }

        // Export Leads Sortable Column Start
        $("#container-export-column-picker #shown-columns").sortable({
            update: function(event, ui) {
                logColumnOrder();
            }
        }).disableSelection();

        // Move selected items between lists
        $('#container-export-column-picker #add-selected').click(function() { moveSelected('#container-export-column-picker #available-columns', '#container-export-column-picker #shown-columns'); });
        $('#container-export-column-picker #remove-selected').click(function() { moveSelected('#container-export-column-picker #shown-columns', '#container-export-column-picker #available-columns'); });
        $('#container-export-column-picker #add-all').click(function() { moveAll('#container-export-column-picker #available-columns', '#container-export-column-picker #shown-columns'); });
        $('#container-export-column-picker #remove-all').click(function() { moveAll('#container-export-column-picker #shown-columns', '#container-export-column-picker #available-columns'); });

        // Reordering Functions
        $('#container-export-column-picker #move-up').click(function() { moveOption(-1); });
        $('#container-export-column-picker #move-down').click(function() { moveOption(1); });
        $('#container-export-column-picker #move-top').click(function() { moveOptionToEnd(false); });
        $('#container-export-column-picker #move-bottom').click(function() { moveOptionToEnd(true); });

        function moveOption(direction) {
            let selected = $('#container-export-column-picker #shown-columns option:selected');
            if (direction === -1) {
                selected.first().prev().before(selected);
            } else {
                selected.last().next().after(selected);
            }
        }

        function moveOptionToEnd(toBottom) {
            let selected = $('#container-export-column-picker #shown-columns option:selected');
            if (toBottom) {
                $('#container-export-column-picker #shown-columns').append(selected);
            } else {
                $('#container-export-column-picker #shown-columns').prepend(selected);
            }
        }

        function getExportColumnOrder() {
            return $('#container-export-column-picker #shown-columns option').map(function() { return $(this).val(); }).get();
        }
        // end Export Leads Sortable Column

        // Leads listing Sortable Column Start
        $("#container-listing-column-picker #shown-columns").sortable({
            update: function(event, ui) {
                logColumnOrder();
            }
        }).disableSelection();

        // Move selected items between lists
        $('#container-listing-column-picker #add-selected').click(function() { moveSelected('#container-listing-column-picker #available-columns', '#container-listing-column-picker #shown-columns'); });
        $('#container-listing-column-picker #remove-selected').click(function() { moveSelected('#container-listing-column-picker #shown-columns', '#container-listing-column-picker #available-columns'); });
        $('#container-listing-column-picker #add-all').click(function() { moveAll('#container-listing-column-picker #available-columns', '#container-listing-column-picker #shown-columns'); });
        $('#container-listing-column-picker #remove-all').click(function() { moveAll('#container-listing-column-picker #shown-columns', '#container-listing-column-picker #available-columns'); });

        // Reordering Functions
        $('#container-listing-column-picker #move-up').click(function() { moveOption(-1); });
        $('#container-listing-column-picker #move-down').click(function() { moveOption(1); });
        $('#container-listing-column-picker #move-top').click(function() { moveOptionToEnd(false); });
        $('#container-listing-column-picker #move-bottom').click(function() { moveOptionToEnd(true); });

        function moveOption(direction) {
            let selected = $('#container-listing-column-picker #shown-columns option:selected');
            if (direction === -1) {
                selected.first().prev().before(selected);
            } else {
                selected.last().next().after(selected);
            }
        }

        function moveOptionToEnd(toBottom) {
            let selected = $('#container-listing-column-picker #shown-columns option:selected');
            if (toBottom) {
                $('#container-listing-column-picker #shown-columns').append(selected);
            } else {
                $('#container-listing-column-picker #shown-columns').prepend(selected);
            }
        }

        function getListingColumnOrder() {
            return $('#container-listing-column-picker #shown-columns option').map(function() { return $(this).val(); }).get();
        }
        // end Leads listing Sortable Column


        // Handle button click for export
        $("#btn-start-lead-export").on("click", function () {
            $('.error-export').addClass('d-none');
            let formDataArray = $("#form-export-lead-data").serializeArray(); // Get form data as an array
            const selectedExportColumns = getExportColumnOrder();
            // const errorMessageElement = $("#form-export-lead-data .error-export-column-selection");

            // errorMessageElement
            //     .text(selectedExportColumns.length ? '' : 'Please select columns for export')
            //     .toggleClass('d-none', selectedExportColumns.length > 0);
            // if (!selectedExportColumns.length) {
            //     return false;
            // }


            // Convert form data to a key-value object
            let formDataObject = {};
            $.each(formDataArray, function (i, field) {
                formDataObject[field.name] = field.value;
            });

            // Collect selected export formats
            let selectedFormats = [];
            $("input[name='export_formats[]']:checked").each(function () {
                selectedFormats.push($(this).val());
            });

            delete(formDataObject['export_formats[]']);

            formDataObject['export_formats'] = selectedFormats;

            let export_type = $("#form-export-lead-data input[name='export_type']:checked").val();
            if (export_type == "export_filtered_data") {
                // Handle Other Filter values
                let search_value = $('#search_value').val();
                let source_site_ids = $('#source_site_id').val();
                let campaign_list_ids = $('#campaign_list_id').val();

                // Handle date subscribed range
                let dateSubscribed = $('#date_subscribed_range').val().split(" - ");
                let date_subscribed_from = dateSubscribed[0] || '';
                let date_subscribed_to = dateSubscribed[1] || '';

                // Handle import date range
                let importDate = $('#import_date_range').val().split(" - ");
                import_date_from = importDate[0] || '';
                import_date_to = importDate[1] || '';

                let filter_data_params = {};

                if (search_value) {
                    filter_data_params['search_value'] = search_value;
                }

                if (source_site_ids && Array.isArray(source_site_ids) && source_site_ids.length) {
                    filter_data_params['source_site_id'] = source_site_ids;
                }

                if (campaign_list_ids && Array.isArray(campaign_list_ids) && campaign_list_ids.length) {
                    filter_data_params['campaign_list_id'] = campaign_list_ids;
                }

                if (date_subscribed_from && date_subscribed_to) {
                    filter_data_params['date_subscribed'] = {
                        from: date_subscribed_from,
                        to: date_subscribed_to
                    };
                }

                if (import_date_from && import_date_to) {
                    filter_data_params['import_date'] = {
                        from: import_date_from,
                        to: import_date_to
                    };
                }

                if ($("#select_amount").val()) {
                    if (($("#select_amount").val() == 'tax_debt_amount') && $('#tax_debt_amount_operator').val() && $('#tax_debt_amount').val()) {
                        let tax_debt_amount_operator = $('#tax_debt_amount_operator').val();
                        let tax_debt_amount_value = $('#tax_debt_amount').val();

                        if (tax_debt_amount_operator && tax_debt_amount_value) {
                            filter_data_params['tax_debt_amount'] = {
                                operator: tax_debt_amount_operator,
                                value: tax_debt_amount_value
                            }
                        }
                    } else if (($("#select_amount").val() == 'cc_debt_amount') && $('#cc_debt_amount_operator').val() && $('#cc_debt_amount').val()) {
                        let cc_debt_amount_operator = $('#cc_debt_amount_operator').val();
                        let cc_debt_amount_value = $('#cc_debt_amount').val();

                        if (cc_debt_amount_operator && cc_debt_amount_value) {
                            filter_data_params['cc_debt_amount'] = {
                                operator: cc_debt_amount_operator,
                                value: cc_debt_amount_value
                            }
                        }
                    }
                }
                formDataObject.filters = filter_data_params;
            }

            formDataObject.export_columns = selectedExportColumns;

            $('#preloader').show();
            $.ajax({
                url: "{{ route('leads.export') }}", // Laravel named route
                type: "POST",
                contentType: "application/json", // Ensure JSON request
                dataType: "json", // Expect JSON response
                data: JSON.stringify({
                    schedule_lead_export_data: formDataObject // Convert data to JSON
                }),
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}" // CSRF token for security
                },
                success: function(response) {
                    $('#preloader').hide();
                    toastr.success(response.message);
                    setTimeout(() => {
                        hideExportLeadsModal();
                    }, 500);  
                },
                error: function(xhr, status, error) {
                    $('#preloader').hide();
                    const server_error_message = xhr?.responseJSON?.message || 'Something went wrong';
                    const server_errors = xhr?.responseJSON?.errors || {}; 

                    console.log("server_error_message:", server_error_message);
                    console.log("server_errors:", server_errors);

                    // Loop through errors and display them in corresponding elements
                    $.each(server_errors, function(field, messages) {
                        const errorElement = $(`.error-export.${field}`); // Select by field class
                        if (errorElement.length) {
                            errorElement.removeClass("d-none").text(messages[0]); // Show first error message
                        }
                    });
                    toastr.error(server_error_message);
                }
            });
        });

        function showLeadsListingColumnSelectionModal() {
            $('#leads-listing-column-selection-model').modal('show');
        }

        function hideLeadsListingColumnSelectionModal() {
            $('#leads-listing-column-selection-model').modal('hide');
            $("#column-customisation-settings").removeClass('setting-on').addClass('setting-off');
        }

        $('#column-customisation-settings').click(function() {
            if ($(this).hasClass('setting-off')) {
                $(this).removeClass('setting-off').addClass('setting-on');

                showLeadsListingColumnSelectionModal();
            } else {
                $(this).removeClass('setting-on').addClass('setting-off');
                hideLeadsListingColumnSelectionModal();
            }
        });

        $(document).on('click', '#btn-close-leads-listing-column-selection-model', hideLeadsListingColumnSelectionModal);

        $('#export-in-batches').change(function () {
            const noteText = this.checked
                ? 'Export will be split into multiple files, each containing 100,000 records for large datasets.'
                : 'Export will process all records in a single file.';

            $('#export-in-batch-note').text(noteText);
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
    // Hide both fields initially
        document.querySelectorAll(".tax_debt_amount, .CC_debt_amount").forEach(el => el.style.display = "none");

        document.getElementById("select_amount").addEventListener("change", function () {
            let selectedValue = this.options[this.selectedIndex].text;

            $('#tax_debt_amount_operator').val('');
            $('#tax_debt_amount').val('');
            $('#cc_debt_amount_operator').val('');
            $('#cc_debt_amount').val('');

            // Hide both fields initially
            document.querySelectorAll(".tax_debt_amount, .CC_debt_amount").forEach(el => el.style.display = "none");

            if (selectedValue === "Tax Debt Amount") {
                document.querySelectorAll(".tax_debt_amount").forEach(el => el.style.display = "block");
            } else if (selectedValue === "CC Debt Amount") {
                document.querySelectorAll(".CC_debt_amount").forEach(el => el.style.display = "block");
            }
        });


        const inputField = document.getElementById('file-prefix');
        inputField.addEventListener('input', function() {
            let inputValue = this.value;
            const sanitizedValue = inputValue.replace(/[^a-zA-Z0-9_-]/g, '');
            const hasSpecialChars = inputValue !== sanitizedValue;

            if (hasSpecialChars) {
            toastr.warning('Special characters (except - and _) are not allowed.');
            this.value = sanitizedValue;
            }
            
            this.value = this.value.replace(/\s+/g, '');
        });
    });
</script>
@endsection