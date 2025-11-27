@extends('layout.master')

@section('page-title', config('app.name') . ' - Consumer Insite Contacts Listing')

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

        #consumer-insite-contacts-table {
            table-layout: auto;
            width: 100%;
        }

        #consumer-insite-contacts-table th, #consumer-insite-contacts-table td {
            white-space: nowrap;
            min-width: 50px;
            max-width: 1000px;
            overflow: auto;
        }

        #categoriesList li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        #categoriesList li:last-child {
            border-bottom: none;
        }

        .view-categories-btn {
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
                            Consumer Insite Contacts List
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
                                    <option value="last_name">Last Name</option>
                                    <option value="email">Email</option>
                                    <option value="age">Age</option>
                                    <option value="credit_score">Credit Score</option>
                                    <option value="location_name">Location Name</option>
                                    <option value="result">Result</option>
                                    <option value="resultid">Result ID</option>
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
                            <button id="openExportModelBtn" type="button" class="btn btn-primary me-2"> Export Contacts</button>
                            <button id="openDeleteTestContactsModalBtn" type="button" class="btn btn-danger"> Delete Test Contacts</button>
                        </div>
                        <!-- ./All Filter Options -->
                    </div>
                </div>

                <!-- Consumer Insite Contacts Table -->
                <table class="table table-bordered table-striped" id="consumer-insite-contacts-table" style="width: -webkit-fill-available;">
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

    <!-- Extra Large Listing column Selection Modal -->
    <div class="modal fade" id="consumer-insite-contacts-listing-column-selection-model" tabindex="-1">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title">Consumer Insite Contacts Listing – Column Customization</h5>
                    <button type="button" class="btn-close" id="btn-close-consumer-insite-contacts-listing-column-selection-model"></button>
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

    <!-- Categories Modal -->
    <div class="modal fade" id="categoriesModal" tabindex="-1" aria-labelledby="categoriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoriesModalLabel">Categories</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul id="categoriesList" class="list-unstyled mb-0">
                        <!-- Categories will be populated here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ./Categories Modal -->

    <!-- Extra Large Modal -->
    <div class="modal fade" id="export-contacts-model" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <form id="form-export-contact-data">
                <div class="modal-content">
                    <div class="modal-header border border-bottom-4 p-3">
                        <h5 class="modal-title">Export Consumer Insite Contacts Data</h5>
                        <button id="closeExportModelBtn" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6">
                                <h6 class="mb-3">Choose What to Export:</h6>
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <div class="form-check w-auto">
                                            <input name="export_type" class="form-check-input" type="radio" value="export_filtered_data" id="export-filtered-data" checked>
                                            <label class="form-check-label" for="export-filtered-data"> Export Filtered Contacts </label>
                                        </div>
                                    </div>
                                    <span class="error-export export_type d-none error"></span>
                                </div>

                                <h6 class="mb-0 d-none">Export Frequency:</h6>
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
                                            <input name="title" id="export-title" class="form-control" type="text" placeholder="e.g., Weekly Consumer Insite Contacts Report">
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
                                            <input name="file_prefix" id="file-prefix" class="form-control" type="text" placeholder="e.g., consumer_insite_contacts, contact_data">
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
                                                    @foreach(config('export_fields.ConsumerInsiteContact') as $field)
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
                                                    @foreach(config('export_fields.ConsumerInsiteContact') as $field)
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border border-top-2 p-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btn-start-contact-export">Start Export</button>
                    </div>
                </div>
            <form>
        </div>
    </div>
    <!-- ./Extra Large Modal -->

    <!-- Delete Test Contacts Modal -->
    <div class="modal fade" id="delete-test-contacts-model" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title">Are you sure want to delete test contacts?</h5>
                    <button type="button" class="btn-close" id="closeDeleteTestContactsModalBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="badge bg-success me-2">Total Count <span id="test-contacts-total-count">0</span></span>
                            <span class="badge bg-danger">Display Count <span id="test-contacts-display-count">0</span></span>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-primary" id="test-contacts-date-range"></span>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-striped" id="test-contacts-table">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Age</th>
                                    <th>Credit Score</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody id="test-contacts-table-body">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <small>Displaying test contact data based on "ckmtest@gmail.com" & "ckmtestpixel@gmail.com" Emails. Use the 'Delete Confirm' button to remove selected entries. Please note that this action is permanent and cannot be undone.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelDeleteTestContactsBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTestContactsBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ./Delete Test Contacts Modal -->

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
<script src="{{ asset('vendor/js/jquery-ui.min.js') }}?v={{ currentVersion() }}"></script>
<script>
    const appTimezone = @json(config('app.timezone'));

    $(document).ready(function () {
        var defaultFields = @json($defaultFields);
        var selectedFields = @json($selectedFields);

        $('.menu-item').removeClass('active');
        $('.menu-item-consumer-insite-contacts').addClass('active');

        function updateAvailableColumns() {
            let $select = $('#container-listing-column-picker #available-columns');
            $select.empty();

            $.each(defaultFields, function(key, label) {
                if (!selectedFields.includes(key)) {
                    $select.append($('<option>', {
                        value: key,
                        text: label
                    }));
                }
            });
        }

        function updateShownColumns() {
            let $select = $('#container-listing-column-picker #shown-columns');
            $select.empty();

            $.each(selectedFields, function(index, field) {
                $select.append($('<option>', {
                    value: field,
                    text: defaultFields[field]
                }));
            });
        }

        updateAvailableColumns();
        updateShownColumns();

        // Date validation function
        function validateDates() {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            var isValid = true;

            $('#start_date_error').hide().text('');
            $('#end_date_error').hide().text('');

            if (!startDate && !endDate) {
                return true;
            }

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
                title: 'Email',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'age',
                name: 'age',
                title: 'Age',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'credit_score',
                name: 'credit_score',
                title: 'Credit Score',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'location_name',
                name: 'location_name',
                title: 'Location Name',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'categories',
                name: 'categories',
                title: 'Categories',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    try {
                        // Data from server is already a JSON string, use it directly
                        var categoriesJson = data || '[]';

                        // If it's already a string (JSON), use it as-is
                        // If it's an object/array, stringify it
                        if (typeof categoriesJson !== 'string') {
                            categoriesJson = JSON.stringify(categoriesJson);
                        }

                        // Escape quotes for HTML attribute
                        var escapedJson = categoriesJson.replace(/"/g, '&quot;');

                        return '<button type="button" class="btn btn-sm btn-outline-info view-categories-btn" data-categories="' + escapedJson + '" data-contact-id="' + row.id + '" title="View Categories">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    } catch (e) {
                        console.error('Error processing categories:', e, 'Data:', data);
                        return '<button type="button" class="btn btn-sm btn-outline-info view-categories-btn" data-categories="[]" data-contact-id="' + row.id + '" title="View Categories">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    }
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
                title: 'Result ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                title: 'Created At',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            {
                data: 'updated_at',
                name: 'updated_at',
                title: 'Updated At',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            {
                data: 'deleted_at',
                name: 'deleted_at',
                title: 'Deleted At',
                render: function(data) {
                    return data == 1 ? 'Yes' : 'No';
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
            "age": {
                data: 'age',
                name: 'age',
                title: 'Age',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "credit_score": {
                data: 'credit_score',
                name: 'credit_score',
                title: 'Credit Score',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "location_name": {
                data: 'location_name',
                name: 'location_name',
                title: 'Location Name',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "categories": {
                data: 'categories',
                name: 'categories',
                title: 'Categories',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    try {
                        // Data from server is already a JSON string, use it directly
                        var categoriesJson = data || '[]';

                        // If it's already a string (JSON), use it as-is
                        // If it's an object/array, stringify it
                        if (typeof categoriesJson !== 'string') {
                            categoriesJson = JSON.stringify(categoriesJson);
                        }

                        // Escape quotes for HTML attribute
                        var escapedJson = categoriesJson.replace(/"/g, '&quot;');

                        return '<button type="button" class="btn btn-sm btn-outline-info view-categories-btn" data-categories="' + escapedJson + '" data-contact-id="' + row.id + '" title="View Categories">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    } catch (e) {
                        console.error('Error processing categories:', e, 'Data:', data);
                        return '<button type="button" class="btn btn-sm btn-outline-info view-categories-btn" data-categories="[]" data-contact-id="' + row.id + '" title="View Categories">' +
                            '<i class="bx bx-info-circle"></i></button>';
                    }
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
                title: 'Result ID',
                render: function(data) {
                    return data ? data : 'N/A';
                }
            },
            "created_at": {
                data: 'created_at',
                name: 'created_at',
                title: 'Created At',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            "updated_at": {
                data: 'updated_at',
                name: 'updated_at',
                title: 'Updated At',
                render: function(data) {
                    return (!data || !moment(data, moment.ISO_8601, true).isValid()) ? 'N/A' :
                        moment.tz(data, appTimezone).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            "deleted_at": {
                data: 'deleted_at',
                name: 'deleted_at',
                title: 'Deleted At',
                render: function(data) {
                    return data == 1 ? 'Yes' : 'No';
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
                    var pageInfo = $('#consumer-insite-contacts-table').DataTable().page.info();
                    return pageInfo.start + meta.row + 1;
                }
            });

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

            if ($.fn.DataTable.isDataTable('#consumer-insite-contacts-table')) {
                $('#consumer-insite-contacts-table').DataTable().clear().destroy();
                $('#consumer-insite-contacts-table thead').empty();
            }

            var thead = '<tr>';
            selectedColumns.forEach(col => {
                thead += `<th>${col.title}</th>`;
            });
            thead += '</tr>';
            $('#consumer-insite-contacts-table thead').html(thead);

            // Find the index of created_at column for ordering
            var createdAtIndex = selectedColumns.findIndex(col => col.data === 'created_at' || col.name === 'created_at');
            // If created_at column not found, default to first column (ID)
            var orderColumnIndex = createdAtIndex >= 0 ? createdAtIndex : 0;

            table = $('#consumer-insite-contacts-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                scrollY: "600px",
                scrollX: true,
                scrollCollapse: true,
                fixedHeader: true,
                autoWidth: false,
                pageLength: 50,
                lengthMenu: [
                    [25, 50, 100, 200, 500],
                    [25, 50, 100, 200, 500]
                ],
                order: [[orderColumnIndex, 'desc']],
                ajax: {
                    url: "{{ route('consumer-insite-contacts.index') }}",
                    data: function(d) {
                        d.filter_column = $('#filter_column').val() || '';
                        d.search_value = $('#search_value').val() || '';

                        if (!validateDates()) {
                            return;
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
                url: "{{ route('save.consumer-insite-contact.field.setting') }}",
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
                        hideConsumerInsiteContactsListingColumnSelectionModal();
                        initializeDataTable(selectedFields);
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
                url: "{{ route('reset.consumer-insite-contact.field.setting') }}",
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
                        hideConsumerInsiteContactsListingColumnSelectionModal();
                        initializeDataTable(selectedFields);
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
            if (!validateDates()) {
                return false;
            }
            table.ajax.reload();
        });

        // Reset Filters
        $('.filter-reset-btn').click(function () {
            $('#filter_column').val('');
            $('#search_value').val('');
            $('#start_date, #end_date').val('');
            $('#start_date_error, #end_date_error').hide();
            $('#clear_start_date, #clear_end_date').hide();
            // Find created_at column index for ordering
            var orderColumnIndex = 0;
            table.columns().every(function(index) {
                if (this.dataSrc() === 'created_at') {
                    orderColumnIndex = index;
                    return false; // break
                }
            });
            table.order([[orderColumnIndex, 'desc']]).draw();
        });

        $(".content-wrapper #collapseExample").on("shown.bs.collapse", function () {
            $("#toggle-filter-button").text("Hide Filter Options");
        });

        $(".content-wrapper #collapseExample").on("hidden.bs.collapse", function () {
            $("#toggle-filter-button").text("View Filter Options");
        });

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

        function moveSelected(from, to) {
            $(from + ' option:selected').appendTo(to);
        }

        function moveAll(from, to) {
            $(from + ' option').appendTo(to);
        }

        function logColumnOrder() {
            // Optional: log the order for debugging
        }

        function showConsumerInsiteContactsListingColumnSelectionModal() {
            $('#consumer-insite-contacts-listing-column-selection-model').modal('show');
        }

        function hideConsumerInsiteContactsListingColumnSelectionModal() {
            $('#consumer-insite-contacts-listing-column-selection-model').modal('hide');
            $("#column-customisation-settings").removeClass('setting-on').addClass('setting-off');
        }

        $('#column-customisation-settings').click(function() {
            if ($(this).hasClass('setting-off')) {
                $(this).removeClass('setting-off').addClass('setting-on');
                showConsumerInsiteContactsListingColumnSelectionModal();
            } else {
                $(this).removeClass('setting-on').addClass('setting-off');
                hideConsumerInsiteContactsListingColumnSelectionModal();
            }
        });

        $(document).on('click', '#btn-close-consumer-insite-contacts-listing-column-selection-model', hideConsumerInsiteContactsListingColumnSelectionModal);

        // Handle categories modal
        $(document).on('click', '.view-categories-btn', function() {
            var categoriesJson = $(this).attr('data-categories');
            var categories = [];

            // Debug logging
            console.log('Raw categories data:', categoriesJson);

            try {
                // Unescape HTML entities and parse JSON
                if (categoriesJson) {
                    // Replace &quot; back to "
                    var unescapedJson = categoriesJson.replace(/&quot;/g, '"');
                    console.log('Unescaped JSON:', unescapedJson);
                    categories = JSON.parse(unescapedJson);
                    console.log('Parsed categories:', categories);
                }
            } catch (e) {
                console.error('Error parsing categories JSON:', e, 'Raw data:', categoriesJson);
                categories = [];
            }

            var $list = $('#categoriesList');
            $list.empty();

            if (categories && Array.isArray(categories) && categories.length > 0) {
                console.log('Displaying', categories.length, 'categories');
                categories.forEach(function(category) {
                    // Escape HTML to prevent XSS
                    var safeCategory = $('<div>').text(category).html();
                    $list.append('<li class="mb-2 py-2"><i class="bx bx-check-circle text-primary me-2"></i><span>' + safeCategory + '</span></li>');
                });
            } else {
                console.log('No categories to display');
                $list.append('<li class="text-muted py-2">No categories assigned</li>');
            }

            $('#categoriesModal').modal('show');
        });

        // Export Contacts Modal Functions
        function resetExportForm() {
            $("#form-export-contact-data")[0].reset();

            let availableColumns = $("#container-export-column-picker #available-columns");
            availableColumns.empty();

            @foreach(config('export_fields.ConsumerInsiteContact') as $field)
                availableColumns.append(new Option("{{ ucfirst(str_replace('_', ' ', $field)) }}", "{{ $field }}"));
            @endforeach

            $("#container-export-column-picker #shown-columns").empty();
            $(".error-export").addClass("d-none").text("");
        }

        function showExportContactsModal() {
            $('#export-contacts-model').modal('show');
            $("#export-frequency").off("change").on("change", toggleFrequencyOptions);
        }

        function hideExportContactsModal() {
            $('#export-contacts-model').modal('hide');
            resetExportForm();
        }

        function toggleFrequencyOptions() {
            let frequency = $("#export-frequency").val();
            $(".frequency-option").hide();
            updateNote("");

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

        $(document).on('click', '#openExportModelBtn', function() {
            showExportContactsModal();
        });

        $(document).on('click', '#closeExportModelBtn', function() {
            hideExportContactsModal();
        });

        $("#day-of-week-container, #day-of-month-container, #time-container").hide();

        // Export Contacts Sortable Column Start
        $("#container-export-column-picker #shown-columns").sortable({
            update: function(event, ui) {
                logColumnOrder();
            }
        }).disableSelection();

        $('#container-export-column-picker #add-selected').click(function() { moveSelected('#container-export-column-picker #available-columns', '#container-export-column-picker #shown-columns'); });
        $('#container-export-column-picker #remove-selected').click(function() { moveSelected('#container-export-column-picker #shown-columns', '#container-export-column-picker #available-columns'); });
        $('#container-export-column-picker #add-all').click(function() { moveAll('#container-export-column-picker #available-columns', '#container-export-column-picker #shown-columns'); });
        $('#container-export-column-picker #remove-all').click(function() { moveAll('#container-export-column-picker #shown-columns', '#container-export-column-picker #available-columns'); });

        $('#container-export-column-picker #move-up').click(function() { moveOptionExport(-1); });
        $('#container-export-column-picker #move-down').click(function() { moveOptionExport(1); });
        $('#container-export-column-picker #move-top').click(function() { moveOptionToEndExport(false); });
        $('#container-export-column-picker #move-bottom').click(function() { moveOptionToEndExport(true); });

        function moveOptionExport(direction) {
            let selected = $('#container-export-column-picker #shown-columns option:selected');
            if (direction === -1) {
                selected.first().prev().before(selected);
            } else {
                selected.last().next().after(selected);
            }
        }

        function moveOptionToEndExport(toBottom) {
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

        $("#form-export-contact-data").on("submit", function (e) {
            e.preventDefault();
        });

        $("#btn-start-contact-export").on("click", function () {
            $('.error-export').addClass('d-none');
            let formDataArray = $("#form-export-contact-data").serializeArray();
            const selectedExportColumns = getExportColumnOrder();

            let formDataObject = {};
            $.each(formDataArray, function (i, field) {
                formDataObject[field.name] = field.value;
            });

            let selectedFormats = [];
            $("input[name='export_formats[]']:checked").each(function () {
                selectedFormats.push($(this).val());
            });

            delete(formDataObject['export_formats[]']);
            formDataObject['export_formats'] = selectedFormats;

            let export_type = $("#form-export-contact-data input[name='export_type']:checked").val();
            if (export_type == "export_filtered_data") {
                let filter_column = $('#filter_column').val();
                let search_value = $('#search_value').val();

                if (!validateDates()) {
                    alert('Please fix the date validation errors before exporting.');
                    return false;
                }

                let start_date = $('#start_date').val() || '';
                let end_date = $('#end_date').val() || '';

                let filter_data_params = {};

                if (filter_column) {
                    filter_data_params['filter_column'] = filter_column;
                }

                if (search_value) {
                    filter_data_params['search_value'] = search_value;
                }

                if (start_date || end_date) {
                    filter_data_params['date_range'] = {
                        from: start_date || null,
                        to: end_date || null
                    };
                }
                formDataObject.filters = filter_data_params;
            }

            formDataObject.export_columns = selectedExportColumns;

            $('#preloader').show();
            $.ajax({
                url: "{{ route('consumer-insite-contacts.export') }}",
                type: "POST",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({
                    schedule_contact_export_data: formDataObject
                }),
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#preloader').hide();

                    if (response.instant_export && response.files && response.files.length > 0) {
                        toastr.success(response.message);

                        response.files.forEach(function(file, index) {
                            setTimeout(function() {
                                const iframe = document.createElement('iframe');
                                iframe.style.display = 'none';
                                iframe.src = file.download_url;
                                document.body.appendChild(iframe);

                                setTimeout(function() {
                                    document.body.removeChild(iframe);
                                }, 2000);
                            }, index * 1000);
                        });

                        setTimeout(() => {
                            hideExportContactsModal();
                        }, 1000);
                    } else {
                        toastr.success(response.message);
                        setTimeout(() => {
                            hideExportContactsModal();
                        }, 500);
                    }
                },
                error: function(xhr, status, error) {
                    $('#preloader').hide();
                    const server_error_message = xhr?.responseJSON?.message || 'Something went wrong';
                    const server_errors = xhr?.responseJSON?.errors || {};

                    $.each(server_errors, function(field, messages) {
                        const errorElement = $(`.error-export.${field}`);
                        if (errorElement.length) {
                            errorElement.removeClass("d-none").text(messages[0]);
                        }
                    });
                    toastr.error(server_error_message);
                }
            });
        });

        $('#export-in-batches').change(function () {
            const noteText = this.checked
                ? 'Export will be split into multiple files, each containing 100,000 records for large datasets.'
                : 'Export will process all records in a single file.';

            $('#export-in-batch-note').text(noteText);
        });

        // Delete Test Contacts Modal Functions
        function loadTestContacts() {
            $('#test-contacts-table-body').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

            $.ajax({
                url: '{{ route("consumer-insite-contacts.test-contacts.get") }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#test-contacts-total-count').text(response.total_count);
                        $('#test-contacts-display-count').text(response.display_count);
                        $('#test-contacts-date-range').text(response.date_range || 'N/A');

                        if (response.contacts && response.contacts.length > 0) {
                            let tableRows = '';
                            response.contacts.forEach(function(contact) {
                                tableRows += '<tr>' +
                                    '<td>' + contact.id + '</td>' +
                                    '<td>' + contact.name + '</td>' +
                                    '<td>' + contact.email + '</td>' +
                                    '<td>' + contact.age + '</td>' +
                                    '<td>' + contact.credit_score + '</td>' +
                                    '<td>' + contact.created_at + '</td>' +
                                    '</tr>';
                            });
                            $('#test-contacts-table-body').html(tableRows);
                        } else {
                            $('#test-contacts-table-body').html('<tr><td colspan="6" class="text-center">No test contacts found.</td></tr>');
                        }
                    } else {
                        $('#test-contacts-table-body').html('<tr><td colspan="6" class="text-center text-danger">Error loading test contacts.</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading test contacts:', error);
                    $('#test-contacts-table-body').html('<tr><td colspan="6" class="text-center text-danger">Error loading test contacts. Please try again.</td></tr>');
                }
            });
        }

        function showDeleteTestContactsModal() {
            $('#delete-test-contacts-model').modal('show');
            loadTestContacts();
        }

        function hideDeleteTestContactsModal() {
            $('#delete-test-contacts-model').modal('hide');
        }

        function deleteTestContacts() {
            if (!confirm('Are you sure you want to delete all test contacts? This action cannot be undone.')) {
                return;
            }

            $('#confirmDeleteTestContactsBtn').prop('disabled', true).text('Deleting...');

            $.ajax({
                url: '{{ route("consumer-insite-contacts.test-contacts.delete") }}',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        hideDeleteTestContactsModal();
                        if (typeof table !== 'undefined' && table) {
                            table.ajax.reload();
                        }
                    } else {
                        alert('Error: ' + (response.message || 'Failed to delete test contacts.'));
                    }
                    $('#confirmDeleteTestContactsBtn').prop('disabled', false).text('Confirm');
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting test contacts:', error);
                    alert('Error deleting test contacts. Please try again.');
                    $('#confirmDeleteTestContactsBtn').prop('disabled', false).text('Confirm');
                }
            });
        }

        $(document).on('click', '#openDeleteTestContactsModalBtn', function() {
            showDeleteTestContactsModal();
        });

        $(document).on('click', '#closeDeleteTestContactsModalBtn, #cancelDeleteTestContactsBtn', function() {
            hideDeleteTestContactsModal();
        });

        $(document).on('click', '#confirmDeleteTestContactsBtn', function() {
            deleteTestContacts();
        });

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
    });
</script>
@endsection

