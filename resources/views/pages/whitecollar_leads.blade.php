@extends('layout.master')

@section('page-title', config('app.name') . ' - WhiteCollar Lead Listing')

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

        #whitecollar-leads-table {
            table-layout: auto; /* Allows flexible column sizing */
            width: 100%; /* Ensures table uses available space */
        }

        #whitecollar-leads-table th, #whitecollar-leads-table td {
            white-space: nowrap; /* Prevents text from wrapping */
            min-width: 50px; /* Ensures columns don't shrink too much */
            max-width: 1000px; /* Prevents excessively wide columns */
            overflow: auto;
        }

        /* Remove any separator lines between action buttons */
        .mb-4 button.btn {
            border-left: none !important;
            border-right: none !important;
        }

        .mb-4 button.btn + button.btn {
            border-left: none !important;
        }

        /* Remove any pseudo-elements that might create separator lines */
        .mb-4 button.btn::before,
        .mb-4 button.btn::after {
            display: none !important;
        }

        /* Ensure no outline or box-shadow creates separator effect */
        .mb-4 button.btn:focus,
        .mb-4 button.btn:active {
            box-shadow: none !important;
            outline: none !important;
        }

        .view-cake-response-btn {
            cursor: pointer;
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
                            WhiteCollar Lead Listing
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
                            <div class="col-md-4">
                                <label for="filter_column">Select Column Name</label>
                                <select id="filter_column" class="form-control">
                                    <option value="">Select column Name</option>
                                    <option value="first_name">First Name</option>
                                    <option value="email_address">Email Address</option>
                                    <option value="lead_timestamp">Lead Timestamp</option>
                                    <option value="payout_paid">Payout Paid</option>
                                    <option value="result">Result</option>
                                    <option value="lead_id">Lead ID</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="search_value">Search Filter</label>
                                <input type="text" id="search_value" class="form-control" placeholder="Search Filter">
                            </div>
                        </div>

                        <!-- Date Range Filters -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="mr-2">Start Date</label>
                                <div class="position-relative flex-grow-1">
                                    <input type="date" id="start_date" class="form-control" placeholder="Select Start Date">
                                    <!-- FontAwesome Cross Button -->
                                    <button type="button" id="clear_start_date" class="btn btn-link position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #999; display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="text-danger" id="start_date_error" style="display: none;"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="mr-2">End Date</label>
                                <div class="position-relative flex-grow-1">
                                    <input type="date" id="end_date" class="form-control" placeholder="Select End Date">
                                    <!-- FontAwesome Cross Button -->
                                    <button type="button" id="clear_end_date" class="btn btn-link position-absolute" style="right: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #999; display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="text-danger" id="end_date_error" style="display: none;"></small>
                            </div>
                        </div>

                        <!-- ./Filter Section -->
                        <div class="mb-4">
                            <button id="filterBtn" class="btn btn-primary me-2">Apply Filters</button>
                            <button id="resetBtn" class="btn btn-secondary filter-reset-btn me-2">Reset Filters</button>
                            <button id="openExportModelBtn" type="button" class="btn btn-primary me-2"> Export Leads</button>
                            <button id="openDeleteTestLeadsModalBtn" type="button" class="btn btn-danger"> Delete Test Leads</button>
                            <!-- <button id="exportCsv" class="btn btn-success">Export CSV</button>
                            <button id="exportExcel" class="btn btn-info">Export Excel</button>
                            <button id="exportPdf" class="btn btn-danger">Export PDF</button> -->
                        </div>
                        <!-- ./All Filter Options -->
                    </div>
                </div>

                <!-- Leads Table -->
                <table class="table table-bordered table-striped" id="whitecollar-leads-table" style="width: -webkit-fill-available;">
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
                                                    @foreach(config('export_fields.FlmApiLead') as $field)
                                                        <option value="{{ $field }}">{{ ucfirst(str_replace('_', ' ', $field)) }}</option>
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
                                                <select class="form-control" value="created_at" name="sort_by_field_name">
                                                    @foreach(config('export_fields.FlmApiLead') as $field)
                                                        <option value="{{ $field }}" {{ ($field == 'created_at') ? "selected" : "" }} >{{ ucfirst(str_replace('_', ' ', $field)) }}</option>
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

    <!-- Delete Test Leads Modal -->
    <div class="modal fade" id="delete-test-leads-model" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title">Are you sure want to delete test leads?</h5>
                    <button type="button" class="btn-close" id="closeDeleteTestLeadsModalBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="badge bg-success me-2">Total Count <span id="test-leads-total-count">0</span></span>
                            <span class="badge bg-danger">Display Count <span id="test-leads-display-count">0</span></span>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-primary" id="test-leads-date-range"></span>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-striped" id="test-leads-table">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>First Name</th>
                                    <th>Email Address</th>
                                    <th>Lead ID</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody id="test-leads-table-body">
                                <tr>
                                    <td colspan="5" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <small>Displaying test lead data based on first name or email containing "test". Use the 'Delete Confirm' button to remove selected entries. Please note that this action is permanent and cannot be undone.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelDeleteTestLeadsBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTestLeadsBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ./Delete Test Leads Modal -->

    <!-- Extra Large Listing column Selection Modal -->
    <div class="modal fade" id="leads-listing-column-selection-model" tabindex="-1">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title">WhiteCollar Lead Listing – Column Customization</h5>
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

    <!--    Response Modal -->
    <div class="modal fade" id="cake-response-modal" tabindex="-1" aria-labelledby="cakeResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title" id="cakeResponseModalLabel">XML Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Response Data:</label>
                        <div id="cake-response-content" class="bg-light p-3 rounded" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden; width: 100%;"></div>
                    </div>
                </div>
                <div class="modal-footer border border-top-2 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="copyCakeResponse()">Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ./Cake Response Modal -->

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

    // Function to copy response to clipboard
    function copyCakeResponse() {
        // Get text content from the pre tag or div
        const responseText = $('#cake-response-content pre').text() || $('#cake-response-content').text();
        navigator.clipboard.writeText(responseText).then(function() {
            toastr.success('Response copied to clipboard!');
        }, function(err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = responseText;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                toastr.success('Response copied to clipboard!');
            } catch (err) {
                toastr.error('Failed to copy response.');
            }
            document.body.removeChild(textArea);
        });
    }

    $(document).ready(function () {
        var defaultFields = @json($defaultFields);
        var selectedFields = @json($selectedFields);

        $('.menu-item').removeClass('active');
        $('.menu-item-whitecollar-leads').addClass('active');

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

        // Date validation function
        function validateDates() {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            var isValid = true;

            // Hide previous errors
            $('#start_date_error').hide().text('');
            $('#end_date_error').hide().text('');

            // If both dates are empty, that's okay (no date filtering)
            if (!startDate && !endDate) {
                return true;
            }

            // If both dates are filled, validate that end date is not before start date
            if (startDate && endDate) {
                var start = new Date(startDate);
                var end = new Date(endDate);

                if (end < start) {
                    $('#end_date_error').text('End Date cannot be before Start Date').show();
                    isValid = false;
                }
            }

            return isValid;
        }

        // Validate dates on change
        $('#start_date, #end_date').on('change', function() {
            validateDates();
        });

        // Clear buttons
        $(document).on('click', '#clear_start_date', function() {
            $('#start_date').val('');
            $('#start_date_error').hide();
            $('#clear_start_date').hide();
        });

        $(document).on('click', '#clear_end_date', function() {
            $('#end_date').val('');
            $('#end_date_error').hide();
            $('#clear_end_date').hide();
        });

        // Show clear button when date is selected
        $('#start_date').on('change', function() {
            if ($(this).val()) {
                $('#clear_start_date').show();
            } else {
                $('#clear_start_date').hide();
            }
        });

        $('#end_date').on('change', function() {
            if ($(this).val()) {
                $('#clear_end_date').show();
            } else {
                $('#clear_end_date').hide();
            }
        });

        var defaultListingFields = [
            {
                data: 'id',
                name: 'id',
                title: 'ID',
                visible: false
            },
            {
                data: 'first_name',
                name: 'first_name',
                title: 'First Name',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'email_address',
                name: 'email_address',
                title: 'Email',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'lead_id',
                name: 'lead_id',
                title: 'LeadID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'fetch_paid_response',
                name: 'fetch_paid_response',
                title: 'Cake response',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    try {
                        // Always show the icon button, even if data is empty (same pattern as Categories column)
                        var responseData = data || '';

                        // Use base64 encoding for complex XML to avoid attribute issues
                        var encodedResponse = '';
                        if (responseData) {
                            // Encode to base64 to safely store in data attribute
                            encodedResponse = btoa(unescape(encodeURIComponent(String(responseData))));
                        }

                        return '<button type="button" class="btn btn-sm btn-outline-info view-cake-response-btn" data-response-encoded="' + encodedResponse + '" data-lead-id="' + row.id + '" title="View Cake Response">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    } catch (e) {
                        console.error('Error processing response:', e, 'Data:', data);
                        return '<button type="button" class="btn btn-sm btn-outline-info view-cake-response-btn" data-response-encoded="" data-lead-id="' + row.id + '" title="View Cake Response">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    }
                }
            },
            {
                data: 'payout_paid',
                name: 'payout_paid',
                title: 'Price Paid',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'result',
                name: 'result',
                title: 'Result',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'resultid',
                name: 'resultid',
                title: 'ResultID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                title: 'Created at',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            }
        ];

        var allFields = {
            "first_name": {
                data: 'first_name',
                name: 'first_name',
                title: 'First Name',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "email_address": {
                data: 'email_address',
                name: 'email_address',
                title: 'Email',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "lead_timestamp": {
                data: 'lead_timestamp',
                name: 'lead_timestamp',
                title: 'Lead Timestamp',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "payout_paid": {
                data: 'payout_paid',
                name: 'payout_paid',
                title: 'Price Paid',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "eoapi_success": {
                data: 'eoapi_success',
                name: 'eoapi_success',
                title: 'EOAPI Success',
                render: function(data) {
                    return data ? 'Yes' : 'No';
                }
            },
            "is_email_duplicate": {
                data: 'is_email_duplicate',
                name: 'is_email_duplicate',
                title: 'Is Email Duplicate',
                render: function(data) {
                    return data ? 'Yes' : 'No';
                }
            },
            "result": {
                data: 'result',
                name: 'result',
                title: 'Result',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "resultid": {
                data: 'resultid',
                name: 'resultid',
                title: 'ResultID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "is_ongage": {
                data: 'is_ongage',
                name: 'is_ongage',
                title: 'Is Ongage',
                render: function(data) {
                    return data ? 'Yes' : 'No';
                }
            },
            "ongage_at": {
                data: 'ongage_at',
                name: 'ongage_at',
                title: 'Ongage At',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            "lead_id": {
                data: 'lead_id',
                name: 'lead_id',
                title: 'LeadID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "created_at": {
                data: 'created_at',
                name: 'created_at',
                title: 'Created at',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            "fetch_paid_response": {
                data: 'fetch_paid_response',
                name: 'fetch_paid_response',
                title: 'Cake response',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    try {
                        // Always show the icon button, even if data is empty (same pattern as Categories column)
                        var responseData = data || '';

                        // Escape quotes and XML characters for HTML attribute
                        // Use base64 encoding for complex XML to avoid attribute issues
                        var encodedResponse = '';
                        if (responseData) {
                            // Encode to base64 to safely store in data attribute
                            encodedResponse = btoa(unescape(encodeURIComponent(String(responseData))));
                        }

                        return '<button type="button" class="btn btn-sm btn-outline-info view-cake-response-btn" data-response-encoded="' + encodedResponse + '" data-lead-id="' + row.id + '" title="View Cake Response">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    } catch (e) {
                        console.error('Error processing response:', e, 'Data:', data);
                        return '<button type="button" class="btn btn-sm btn-outline-info view-cake-response-btn" data-response-encoded="" data-lead-id="' + row.id + '" title="View Cake Response">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    }
                }
            },
            "ongage_response": {
                data: 'ongage_response',
                name: 'ongage_response',
                title: 'Ongage Response',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "fetch_paid_response": {
                data: 'fetch_paid_response',
                name: 'fetch_paid_response',
                title: 'Fetch Paid Response',
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
                    var pageInfo = $('#whitecollar-leads-table').DataTable().page.info();
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

            if ($.fn.DataTable.isDataTable('#whitecollar-leads-table')) {
                $('#whitecollar-leads-table').DataTable().clear().destroy();
                $('#whitecollar-leads-table thead').empty();
            }

            // Add table headers dynamically
            var thead = '<tr>';
            selectedColumns.forEach(col => {
                thead += `<th>${col.title}</th>`;
            });
            thead += '</tr>';
            $('#whitecollar-leads-table thead').html(thead);

            // Find the index of created_at column for ordering
            var createdAtIndex = selectedColumns.findIndex(col => col.data === 'created_at' || col.name === 'created_at');
            // If created_at column not found, default to first column (ID)
            var orderColumnIndex = createdAtIndex >= 0 ? createdAtIndex : 0;

            // Reinitialize DataTable
            table = $('#whitecollar-leads-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                scrollY: "600px", // Enables vertical scrolling with increased height
                scrollX: true, // Enables horizontal scrolling for wide tables
                scrollCollapse: true, // Ensures the table fits inside the scroll container
                fixedHeader: true, // Keeps the header aligned when scrolling
                autoWidth: false, // Prevents column width distortion
                pageLength: 50, // Default to 50 entries per page for better UX
                lengthMenu: [
                    [25, 50, 100, 200, 500], // Values for page length
                    [25, 50, 100, 200, 500]  // Labels shown in dropdown
                ],
                order: [[orderColumnIndex, 'desc']],
                ajax: {
                    url: "{{ route('whitecollar-leads.index') }}",
                    data: function(d) {
                        d.filter_column = $('#filter_column').val() || '';
                        d.search_value = $('#search_value').val() || '';

                        // Validate dates before sending
                        if (!validateDates()) {
                            return; // Don't send request if validation fails
                        }

                        d.start_date = $('#start_date').val() || '';
                        d.end_date = $('#end_date').val() || '';
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
                url: "{{ route('save.whitecollar-lead.field.setting') }}", // Backend route
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
                url: "{{ route('reset.whitecollar-lead.field.setting') }}", // Backend route
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
            // Validate dates before applying filter
            if (!validateDates()) {
                return false;
            }
            table.ajax.reload();
        });

        // Reset Filters
        $('.filter-reset-btn').click(function () {
            $('#filter_column').val('');
            $('#search_value').val('');
            $('#select_amount').val('');
            $('#campaign_list_id').val(null).trigger('change'); // Reset Select2
            $('#start_date, #end_date').val('');
            $('#start_date_error, #end_date_error').hide();
            $('#clear_start_date, #clear_end_date').hide();


            $("#tax_debt_amount_operator, #tax_debt_amount, #cc_debt_amount_operator, #cc_debt_amount").val("");

            // Find created_at column index for ordering
            var orderColumnIndex = 0;
            if (typeof table !== 'undefined' && table) {
                table.columns().every(function(index) {
                    if (this.dataSrc() === 'created_at') {
                        orderColumnIndex = index;
                        return false; // break
                    }
                });
            }
            $('.CC_debt_amount, .tax_debt_amount').hide();
            table.order([[orderColumnIndex, 'desc']]).draw();
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

            @foreach(config('export_fields.AllContact') as $field)
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
                let filter_column = $('#filter_column').val();
                let search_value = $('#search_value').val();
                let campaign_list_ids = $('#campaign_list_id').val();

                // Validate dates before export
                if (!validateDates()) {
                    alert('Please fix the date validation errors before exporting.');
                    return false;
                }

                // Handle start and end date
                let start_date = $('#start_date').val() || '';
                let end_date = $('#end_date').val() || '';

                let filter_data_params = {};

                if (filter_column) {
                    filter_data_params['filter_column'] = filter_column;
                }

                if (search_value) {
                    filter_data_params['search_value'] = search_value;
                }

                if (campaign_list_ids && Array.isArray(campaign_list_ids) && campaign_list_ids.length) {
                    filter_data_params['campaign_list_id'] = campaign_list_ids;
                }

                // Date range filter - supports partial dates
                if (start_date || end_date) {
                    filter_data_params['date_range'] = {
                        from: start_date || null,
                        to: end_date || null
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
                url: "{{ route('whitecollar-leads.export') }}", // Laravel named route
                type: "POST",
                contentType: "application/json", // Ensure JSON request
                dataType: "json", // Expect JSON response
                data: JSON.stringify({
                    schedule_lead_export_data: formDataObject
                }),
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}" // CSRF token for security
                },
                success: function(response) {
                    $('#preloader').hide();

                    // Check if this is an instant export with files
                    if (response.instant_export && response.files && response.files.length > 0) {
                        // Show success message
                        toastr.success(response.message);

                        // Download all files automatically without confirmation
                        response.files.forEach(function(file, index) {
                            setTimeout(function() {
                                // Create a hidden iframe for download to avoid popup blockers
                                const iframe = document.createElement('iframe');
                                iframe.style.display = 'none';
                                iframe.src = file.download_url;
                                document.body.appendChild(iframe);

                                // Remove iframe after download starts
                                setTimeout(function() {
                                    document.body.removeChild(iframe);
                                }, 2000);
                            }, index * 1000); // 1 second delay between each download to ensure files are ready
                        });

                        setTimeout(() => {
                            hideExportLeadsModal();
                        }, 1000);
                    } else {
                        // Regular scheduled export
                    toastr.success(response.message);
                    setTimeout(() => {
                        hideExportLeadsModal();
                    }, 500);
                    }
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

        const selectAmount = document.getElementById("select_amount");
        if (selectAmount) {
            selectAmount.addEventListener("change", function () {
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
        }

        const inputField = document.getElementById('file-prefix');
        if (inputField) {
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
        }

        // Delete Test Leads Modal Functions
        function loadTestLeads() {
            $('#test-leads-table-body').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');

            $.ajax({
                url: '{{ route("whitecollar-leads.test-leads.get") }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update counts and date range
                        $('#test-leads-total-count').text(response.total_count);
                        $('#test-leads-display-count').text(response.display_count);
                        $('#test-leads-date-range').text(response.date_range || 'N/A');

                        // Populate table
                        if (response.leads && response.leads.length > 0) {
                            let tableRows = '';
                            response.leads.forEach(function(lead) {
                                tableRows += '<tr>' +
                                    '<td>' + lead.id + '</td>' +
                                    '<td>' + (lead.first_name || 'N/A') + '</td>' +
                                    '<td>' + (lead.email_address || 'N/A') + '</td>' +
                                    '<td>' + (lead.lead_id || 'N/A') + '</td>' +
                                    '<td>' + lead.created_at + '</td>' +
                                    '</tr>';
                            });
                            $('#test-leads-table-body').html(tableRows);
                        } else {
                            $('#test-leads-table-body').html('<tr><td colspan="5" class="text-center">No test leads found.</td></tr>');
                        }
                    } else {
                        $('#test-leads-table-body').html('<tr><td colspan="5" class="text-center text-danger">Error loading test leads.</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading test leads:', error);
                    $('#test-leads-table-body').html('<tr><td colspan="5" class="text-center text-danger">Error loading test leads. Please try again.</td></tr>');
                }
            });
        }

        function showDeleteTestLeadsModal() {
            $('#delete-test-leads-model').modal('show');
            loadTestLeads();
        }

        function hideDeleteTestLeadsModal() {
            $('#delete-test-leads-model').modal('hide');
        }

        function deleteTestLeads() {
            if (!confirm('Are you sure you want to delete all test leads? This action cannot be undone.')) {
                return;
            }

            $('#confirmDeleteTestLeadsBtn').prop('disabled', true).text('Deleting...');

            $.ajax({
                url: '{{ route("whitecollar-leads.test-leads.delete") }}',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        hideDeleteTestLeadsModal();
                        // Reload the leads table
                        if (typeof table !== 'undefined' && table) {
                            table.ajax.reload();
                        }
                    } else {
                        alert('Error: ' + (response.message || 'Failed to delete test leads.'));
                    }
                    $('#confirmDeleteTestLeadsBtn').prop('disabled', false).text('Confirm');
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting test leads:', error);
                    alert('Error deleting test leads. Please try again.');
                    $('#confirmDeleteTestLeadsBtn').prop('disabled', false).text('Confirm');
                }
            });
        }

        // Event handlers
        $(document).on('click', '#openDeleteTestLeadsModalBtn', function() {
            showDeleteTestLeadsModal();
        });

        $(document).on('click', '#closeDeleteTestLeadsModalBtn, #cancelDeleteTestLeadsBtn', function() {
            hideDeleteTestLeadsModal();
        });

        $(document).on('click', '#confirmDeleteTestLeadsBtn', function() {
            deleteTestLeads();
        });

        // Function to format XML for display (like reference file but ensures proper formatting)
        function formatXMLForDisplay(xmlString) {
            if (!xmlString || xmlString.trim() === '') {
                return '';
            }

            try {
                // Unescape any escaped characters from database
                xmlString = xmlString.replace(/\\"/g, '"')
                                    .replace(/\\\//g, '/')
                                    .replace(/\\r\\n/g, '\n')
                                    .replace(/\\n/g, '\n')
                                    .replace(/\\t/g, '\t');

                // Check if already formatted (has proper indentation)
                if (xmlString.indexOf('\n') > -1 && (xmlString.indexOf('  <') > -1 || xmlString.indexOf('\r\n  <') > -1)) {
                    // Already formatted, just normalize line endings
                    return xmlString.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                }

                // Need to format it - use DOMParser
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xmlString.replace(/>\s+</g, '><').trim(), 'text/xml');

                // Check for parsing errors
                if (xmlDoc.getElementsByTagName('parsererror').length > 0) {
                    // If parsing fails, return as-is
                    return xmlString;
                }

                // Format using DOM tree (2 spaces indentation)
                return formatXMLFromDOM(xmlDoc, 0);

            } catch (e) {
                console.error('Error formatting XML:', e);
                return xmlString;
            }
        }

        // XML formatting function using DOMParser (reliable method)
        function formatXMLSimple(xmlString) {
            if (!xmlString || xmlString.trim() === '') {
                return '';
            }

            try {
                // Unescape any escaped characters from database
                xmlString = xmlString.replace(/\\"/g, '"')
                                    .replace(/\\\//g, '/')
                                    .replace(/\\r\\n/g, '\n')
                                    .replace(/\\n/g, '\n')
                                    .replace(/\\t/g, '\t');

                // Check if already formatted (has newlines and proper spacing)
                if (xmlString.indexOf('\n') > -1 && xmlString.match(/^\s+</m)) {
                    // Already formatted, just clean it up
                    return xmlString.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                }

                // Remove existing formatting to start fresh
                xmlString = xmlString.replace(/>\s+</g, '><').trim();

                // Try to parse with DOMParser
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xmlString, 'text/xml');

                // Check for parsing errors
                if (xmlDoc.getElementsByTagName('parsererror').length > 0) {
                    // If parsing fails, use regex-based formatting
                    console.log('DOMParser failed, using regex formatting');
                    return formatXMLWithRegex(xmlString);
                }

                // Format using DOM tree (2 spaces indentation)
                var result = formatXMLFromDOM(xmlDoc, 0);
                if (result && result.trim()) {
                    return result;
                } else {
                    // Fallback to regex
                    return formatXMLWithRegex(xmlString);
                }

            } catch (e) {
                console.error('Error formatting XML:', e);
                // Fallback to regex-based formatting
                return formatXMLWithRegex(xmlString);
            }
        }

        // Format XML from DOM tree
        function formatXMLFromDOM(node, indent) {
            var formatted = '';
            var tab = '  '; // 2 spaces
            var indentStr = '';
            for (var i = 0; i < indent; i++) {
                indentStr += tab;
            }

            if (node.nodeType === 9) {
                // Document node - process children
                // Get XML declaration if it exists
                var xmlDecl = '';
                if (node.xmlVersion) {
                    xmlDecl = '<' + '?xml version="' + node.xmlVersion + '"';
                    if (node.xmlEncoding) {
                        xmlDecl += ' encoding="' + node.xmlEncoding + '"';
                    }
                    xmlDecl += '?>';
                    formatted += xmlDecl + '\n';
                } else {
                    // Default XML declaration
                    formatted += '<' + '?xml version="1.0" encoding="utf-8"?>\n';
                }
                if (node.documentElement) {
                    formatted += formatXMLFromDOM(node.documentElement, 0);
                }
            } else if (node.nodeType === 1) {
                // Element node
                formatted += indentStr + '<' + node.nodeName;

                // Add attributes
                if (node.attributes && node.attributes.length > 0) {
                    for (var j = 0; j < node.attributes.length; j++) {
                        var attr = node.attributes[j];
                        formatted += ' ' + attr.name + '="' + attr.value + '"';
                    }
                }

                // Check children
                var hasElementChildren = false;
                var textContent = '';

                for (var k = 0; k < node.childNodes.length; k++) {
                    var child = node.childNodes[k];
                    if (child.nodeType === 1) {
                        hasElementChildren = true;
                        break;
                    } else if (child.nodeType === 3) {
                        var text = child.textContent.trim();
                        if (text) {
                            textContent += text;
                        }
                    }
                }

                if (hasElementChildren) {
                    // Has child elements
                    formatted += '>\n';
                    for (var l = 0; l < node.childNodes.length; l++) {
                        var childNode = node.childNodes[l];
                        if (childNode.nodeType === 1) {
                            formatted += formatXMLFromDOM(childNode, indent + 1);
                        } else if (childNode.nodeType === 3 && childNode.textContent.trim()) {
                            formatted += indentStr + tab + childNode.textContent.trim() + '\n';
                        }
                    }
                    formatted += indentStr + '</' + node.nodeName + '>\n';
                } else if (textContent) {
                    // Has only text content
                    formatted += '>' + textContent + '</' + node.nodeName + '>\n';
                } else {
                    // Empty element - self-closing
                    formatted += ' />\n';
                }
            }

            return formatted;
        }

        // Fallback regex-based formatting
        function formatXMLWithRegex(xmlString) {
            var formatted = '';
            var indent = 0;
            var tab = '  '; // 2 spaces

            // Add line breaks
            xmlString = xmlString.replace(/>/g, '>\n').replace(/</g, '\n<');
            var lines = xmlString.split('\n');

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim();
                if (!line) continue;

                if (line.startsWith('<?xml')) {
                    formatted += line + '\n';
                } else if (line.startsWith('</')) {
                    indent = Math.max(0, indent - 1);
                    for (var j = 0; j < indent; j++) {
                        formatted += tab;
                    }
                    formatted += line + '\n';
                } else if (line.endsWith('/>')) {
                    for (var k = 0; k < indent; k++) {
                        formatted += tab;
                    }
                    formatted += line + '\n';
                } else if (line.startsWith('<')) {
                    for (var l = 0; l < indent; l++) {
                        formatted += tab;
                    }
                    formatted += line + '\n';
                    indent++;
                } else {
                    if (line) {
                        for (var m = 0; m < indent; m++) {
                            formatted += tab;
                        }
                        formatted += line + '\n';
                    }
                }
            }

            return formatted;
        }

        // Improved XML formatting function (fallback)
        function formatXMLImproved(xmlString) {
            var formatted = '';
            var indent = 0;
            var tab = '  '; // 2 spaces for indentation (matching first image style)
            var newline = String.fromCharCode(10); // Newline character

            // Remove existing formatting but preserve structure
            xmlString = xmlString.replace(/>\s+</g, '><');
            xmlString = xmlString.replace(/^\s+|\s+$/g, ''); // Trim

            // Use regex to find all tags and text
            var regex = /(<\/?[^>]+>|[^<]+)/g;
            var matches = xmlString.match(regex);

            if (!matches) {
                return xmlString;
            }

            for (var i = 0; i < matches.length; i++) {
                var match = matches[i].trim();
                if (!match) continue;

                if (match.startsWith('</')) {
                    // Closing tag
                    indent = Math.max(0, indent - 1);
                    for (var j = 0; j < indent; j++) {
                        formatted += tab;
                    }
                    formatted += match + newline;
                } else if (match.startsWith('<?')) {
                    // XML declaration
                    formatted += match + newline;
                } else if (match.startsWith('<') && match.endsWith('/>')) {
                    // Self-closing tag
                    for (var k = 0; k < indent; k++) {
                        formatted += tab;
                    }
                    formatted += match + newline;
                } else if (match.startsWith('<')) {
                    // Opening tag
                    for (var l = 0; l < indent; l++) {
                        formatted += tab;
                    }
                    formatted += match + newline;
                    indent++;
                } else {
                    // Text content
                    var text = match.trim();
                    if (text) {
                        for (var m = 0; m < indent; m++) {
                            formatted += tab;
                        }
                        formatted += text + newline;
                    }
                }
            }

            return formatted;
        }

        // Format XML node recursively (2 spaces indentation like first image)
        function formatXMLNode(node, indent) {
            var formatted = '';
            var tab = '  '; // 2 spaces for indentation (matching first image style)
            var indentStr = '';
            for (var i = 0; i < indent; i++) {
                indentStr += tab;
            }

            if (node.nodeType === 1) { // Element node
                formatted += indentStr + '<' + node.nodeName;

                // Add attributes
                if (node.attributes && node.attributes.length > 0) {
                    for (var j = 0; j < node.attributes.length; j++) {
                        var attr = node.attributes[j];
                        formatted += ' ' + attr.name + '="' + attr.value + '"';
                    }
                }

                // Check if node has children
                if (node.childNodes && node.childNodes.length > 0) {
                    var hasElementChildren = false;
                    var textContent = '';
                    var hasOnlyWhitespace = true;

                    for (var k = 0; k < node.childNodes.length; k++) {
                        var child = node.childNodes[k];
                        if (child.nodeType === 1) { // Element node
                            hasElementChildren = true;
                            hasOnlyWhitespace = false;
                            break;
                        } else if (child.nodeType === 3) { // Text node
                            var text = child.textContent;
                            if (text && text.trim()) {
                                textContent += text.trim();
                                hasOnlyWhitespace = false;
                            }
                        }
                    }

                    if (hasElementChildren) {
                        // Node has child elements
                        formatted += '>\n';
                        for (var l = 0; l < node.childNodes.length; l++) {
                            var childNode = node.childNodes[l];
                            if (childNode.nodeType === 1) {
                                formatted += formatXMLNode(childNode, indent + 1);
                            } else if (childNode.nodeType === 3 && childNode.textContent.trim()) {
                                formatted += indentStr + tab + childNode.textContent.trim() + '\n';
                            }
                        }
                        formatted += indentStr + '</' + node.nodeName + '>\n';
                    } else if (textContent && !hasOnlyWhitespace) {
                        // Node has only text content
                        formatted += '>' + textContent + '</' + node.nodeName + '>\n';
                    } else {
                        // Empty node - use self-closing tag
                        formatted += ' />\n';
                    }
                } else {
                    // Self-closing tag
                    formatted += ' />\n';
                }
            } else if (node.nodeType === 10) {
                // Document type or XML declaration - skip
                return '';
            }

            return formatted;
        }

        // Handle click on Cake Response icon (same pattern as Categories modal)
        $(document).on('click', '.view-cake-response-btn', function() {
            var encodedData = $(this).attr('data-response-encoded');
            var response = '';

            // Debug logging
            console.log('Encoded response data:', encodedData);

            try {
                // Decode from base64
                if (encodedData && encodedData !== '') {
                    try {
                        response = decodeURIComponent(escape(atob(encodedData)));
                        console.log('Decoded response:', response);
                    } catch (e) {
                        // If base64 decode fails, try direct decode
                        try {
                            response = atob(encodedData);
                        } catch (e2) {
                            console.error('Error decoding base64:', e2);
                            response = '';
                        }
                    }
                }
            } catch (e) {
                console.error('Error processing response:', e, 'Raw data:', encodedData);
                response = '';
            }

            // If no response data, show message
            if (!response || response === '' || response.trim() === '') {
                $('#cake-response-content').html('<pre style="margin: 0; white-space: pre; font-family: monospace; font-size: 0.9rem;">No response data available.</pre>');
            } else {
                // Format XML with proper indentation (like reference file but with formatting)
                var formattedXML = formatXMLForDisplay(response);
                // Escape HTML entities and wrap in <pre> (like reference file)
                var escapedXmlContent = formattedXML.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                var formattedXmlContent = '<pre style="margin: 0; white-space: pre; font-family: monospace; font-size: 0.9rem; line-height: 1.6;">' + escapedXmlContent + '</pre>';
                $('#cake-response-content').html(formattedXmlContent);
            }

            $('#cake-response-modal').modal('show');
        });
    });
</script>
@endsection

