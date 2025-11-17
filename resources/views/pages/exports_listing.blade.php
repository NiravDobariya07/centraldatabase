@extends('layout.master')

@section('page-title', config('app.name') . ' - Export Schedule List')
@section('custom-page-style')
<style>
.modal-body .row .col-md-6 {
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
.export_sett_data {
    border-bottom: 1px solid #edededa8;
    padding: 0 0 5px 0;
}

.export_sett_data label{
    color: #000000b8;
    margin: 0 5px 0 0;
    font-weight: 600;
}
</style>
@endsection
@section('page-content')

@php
    use App\Constants\AppConstants;
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">

        <div class="col-12">
            <div class="nav-align-top mb-4">
                <ul class="nav nav-pills mb-3 nav-fill col-md-6 col-lg-5 col-xl-5 col-xxl-4 " role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'export-schedule' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-justified-home" aria-controls="navs-pills-justified-home"
                            aria-selected="{{ $activeTab == 'export-schedule' ? 'true' : 'false' }}" data-tab-type="export-schedule">
                            <i class="bx bx-export me-1"></i>
                            Export Schedule
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'export-history' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-justified-profile" aria-controls="navs-pills-justified-profile"
                            aria-selected="{{ $activeTab == 'export-history' ? 'true' : 'false' }}" data-tab-type="export-history">
                            <i class="bx bx-history me-1"></i>
                            Export History
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade {{ $activeTab == 'export-schedule' ? 'show active' : '' }}" id="navs-pills-justified-home" role="tabpanel">
                        <div class="card-header export_schedule">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fs-3 fw-bolder card-title text-primary">Export Schedule List</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped mt-3" id="exports-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>S.No</th>
                                        <th>User</th>
                                        <th>Title</th>
                                        <th>Frequency</th>
                                        <th>Next Run Time</th>
                                        <th>Last Run Time</th>
                                        <th>Process Status</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>

                        </div>
                    </div>
                    <div class="tab-pane fade {{ $activeTab == 'export-history' ? 'show active' : '' }}" id="navs-pills-justified-profile" role="tabpanel">
                        <div class="card-header export-history">
                            <!-- <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fs-3 fw-bolder card-title text-primary">Export History</h5>
                            </div> -->
                            <div class="row">
                                <div class="col-5">
                                    <h5 class="fs-3 fw-bolder card-title text-primary">Export History</h5>
                                </div>
                                <div class="col-7">
                                    <div class="row d-flex align-items-center">
                                        <div class="col-9 d-flex align-items-center justify-content-end gap-3 pr-0">
                                            <label for="select_export" class="flex-shrink-0">Filter By Export</label>
                                            <select id="select_export" multiple class="form-control select2">
                                            </select>
                                        </div>
                                        <div class="col-3 w-auto ml-0 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary btn-filter">Filter</button>
                                        <button class="btn btn-primary btn-clear-filter">Clear</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="exports-history-table">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>User</th>
                                        <th>File Name</th>
                                        <th>Size</th>
                                        <th>Generated At</th>
                                        <th>Expires At</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="export-leads-model" tabindex="-1" export-id="">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header border border-bottom-4 p-3">
                <h3 class="modal-title">Export Schedule Details</h3>
                <button id="closeExportModelBtn" type="button" class="btn-close" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Title:</label>
                            <span id="export_title"></span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Created By:</label>
                            <span id="export_user_name"></span>
                        </div>
                    </div>

                    <!-- Lead Status -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Description:</label>
                            <span id="export_description"></span>
                        </div>
                    </div>

                    <!-- Email for Export -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>File Prefix:</label>
                            <span id="export_file_prefix"></span>
                        </div>
                    </div>

                    <!-- Total Leads -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Export Formats:</label>
                            <span id="export_export_formats"></span>
                        </div>
                    </div>

                    <!-- Exported By -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Filters:</label>
                            <span id="export_filters"></span>
                        </div>
                    </div>

                    <!-- Export Date -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Additional Data:</label>
                            <span id="export_additional_data"></span>
                        </div>
                    </div>


                    <!-- Export Type -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Columns:</label>
                            <span id="export_columns"></span>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="col-md-12 mb-3">
                        <div class="export_sett_data">
                            <label>Frequency:</label>
                            <span id="export_frequency"></span>
                            <span id="export_frequency_details">
                                <div id="export_frequency_details_day_of_week_container" class="mt-2">
                                    <label class="text-secondary">Day of Week:</label>
                                    <span id="export_day_of_week"></span>
                                </div>
                                <div id="export_frequency_details_day_of_month_container" class="mt-2">
                                    <label class="text-secondary">Day of Month:</label>
                                    <span id="export_day_of_month"></span>
                                </div>
                                <div id="export_frequency_details_time_container" class="mt-2">
                                    <label class="text-secondary">Time:</label>
                                    <span id="export_time"></span>
                                </div>
                            </span>
                        </div>
                    </div>

                    <!-- Exported By -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Next Run At:</label>
                            <span id="export_next_run_at"></span>
                        </div>
                    </div>

                    <!-- Export Date -->
                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Last Run At</label>
                            <span id="export_last_run_at"></span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data">
                            <label>Runing Status</label>
                            <span id="export_runing_status"></span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="export_sett_data row">
                            <div class="col-2 w-auto">
                                <label>Status : </label>
                            </div>
                            <div class="col-10 w-auto">
                                <select id="export_status" class="form-control">
                                @foreach (AppConstants::EXPORT_STATUSES as $key => $value)
                                    @if ($value == 'active')
                                        <option value="{{ $value }}">Active</option>
                                    @elseif ($value == 'paused')
                                        <option value="{{ $value }}">Pause</option>
                                    @elseif ($value == 'stopped')
                                        <option value="{{ $value }}">Stop</option>
                                    @endif
                                @endforeach
                                </select>
                                <span id="export_status_badge"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border border-top-4 p-3">
                <button type="button" class="btn btn-primary btn-update-export-schedule">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger btn-delete-export-schedule">Delete</button>
            </div>
        </div>
    </div>
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
$(document).ready(function() {
    $('.menu-item').removeClass('active');
    $('.menu-item-exports').addClass('active');

    function formatString(inputString) {
        return inputString.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase());
    }

    function getOrdinalSuffix(num) {
        if (num >= 11 && num <= 13) return num + "th"; // Special case for 11th, 12th, 13th
        const suffixes = ["th", "st", "nd", "rd"];
        const lastDigit = num % 10;
        return num + (suffixes[lastDigit] || "th");
    }

    function reInitBootstrapTooltip() {
        // Reinitialize Bootstrap tooltips after each draw
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function getOperatorName(operator) {
        const operatorMap = {
            '>': 'Greater than',
            '<': 'Less than',
            '>=': 'Greater than or equal to',
            '<=': 'Less than or equal to',
            '=': 'Equal to',
            '!=': 'Not equal to'
        };

        return operatorMap[operator] || operator; // Return original operator if not found
    }

    function configureExportSelect() {
        $.ajax({
            url: "{{ route('export.schedule.options-data') }}", // Laravel route
            type: "GET",
            dataType: "json",
            success: function (response) {
                if (response.success && Array.isArray(response.data)) {
                    // Clear previous options
                    $('#select_export').empty();

                    // Map response data to Select2 format
                    let select_options_data = response.data.map((item) => ({
                        id: item.id,
                        text: item.title || "Untitled Export" // Fallback if title is missing
                    }));

                    // Reinitialize Select2 with new data
                    $('#select_export').select2({
                        placeholder: "Select Export Setting",
                        allowClear: true,
                        multiple: true,
                        data: select_options_data
                    });
                } else {
                    console.warn("No export data available.");
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error);
            }
        });
    }

    if (!$.fn.DataTable.isDataTable('#exports-table')) {
        var exportTable = $('#exports-table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            pageLength: 10,
            scrollY: "380px",
            scrollCollapse: true,
            order: [[0, 'desc']], // Sort by Next Run Time
            ajax: {
                url: "{{ route('leads.export.exports-listing') }}",
            },
            columns: [
                { data: 'id', name: 'id', title: 'Id', visible: false, },
                { 
                    data: null, 
                    name: 'serial_number', 
                    title: 'S.No',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1; // Generates serial number dynamically
                    }
                },
                { data: 'user_name', name: 'users.name', title: 'User' }, // Ensure sorting works
                { data: 'title', name: 'title', title: 'Title' },
                {
                    data: 'frequency',
                    name: 'frequency',
                    title: 'Frequency',
                    render: function(data, type, row, meta) {
                        const formattedTime = row?.time ? ` at ${row.time}` : "";
                        const formattedDay = row?.day_of_week ? ` on ${formatString(row.day_of_week)}` : "";
                        const formattedDate = row?.day_of_month ? ` on the ${getOrdinalSuffix(row.day_of_month)}` : "";

                        const tooltips = {
                            one_time: "Runs only once",
                            daily: "Runs every day" + formattedTime,
                            weekly: `Runs weekly${formattedDay}${formattedTime}`,
                            monthly: `Runs monthly${formattedDate}${formattedTime}`,
                            custom: "Runs on a custom schedule"
                        };

                        return `<span class="badge bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="${tooltips[data] || 'Unknown frequency'}">${formatString(data)}</span>`;
                    }
                },
                { data: 'next_run_at', name: 'next_run_at', title: 'Next Run Time' },
                { data: 'last_run_at', name: 'last_run_at', title: 'Last Run Time' },
                {
                    data: 'runing_status',
                    name: 'runing_status',
                    title: 'Process Status',
                    render: function(data, type, row, meta) {
                        const badgeClasses = {
                            scheduled: 'bg-primary',
                            success: 'bg-success',
                            failed: 'bg-danger',
                            pending: 'bg-info',
                            paused: 'bg-warning',
                            stopped: 'bg-danger'
                        };
                        const badgeClass = badgeClasses[data] || 'bg-secondary';

                        const tooltips = {
                            success: `Completed successfully at ${row?.last_run_at}`,
                            scheduled: "Process is scheduled and will run soon",
                            failed: `Execution failed at ${row?.last_run_at}`,
                            pending: `Will be scheduled at ${row?.next_run_at}`
                        };

                        return `<span class="badge ${badgeClass}" data-bs-toggle="tooltip" data-bs-placement="top" title="${tooltips[data] || 'Status unknown'}">${data}</span>`;
                    }
                },
                { 
                    data: 'status', 
                    name: 'status', 
                    title: 'Status',
                    render: function(data) {
                        const badgeClasses = {
                            active: 'bg-success',
                            paused: 'bg-warning',
                            stopped: 'bg-danger'
                        };
                        let badgeClass = badgeClasses[data?.toLowerCase()] || 'bg-secondary';
                        let formattedText = data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Unknown';
                        return `<span class="badge ${badgeClass}">${formattedText}</span>`;
                    }
                },
                { 
                    data: 'action', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false, 
                    title: 'Actions',
                    className: 'export-schedule-list-action'
                }
            ],
            drawCallback: function(settings) {
                reInitBootstrapTooltip();
            }
        });
    }

    if (!$.fn.DataTable.isDataTable('#exports-history-table')) {
        var selected_export_ids = $("#select_export").val()
        var exportHistoryTable = $('#exports-history-table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            pageLength: 10,
            scrollY: "380px",
            scrollCollapse: true,
            order: [[4, 'desc']], // Sort by user_name (now it's in position 1)
            ajax: {
                url: "{{ route('leads.export.exports-files-listing') }}",
                data: function(d) {
                    d.selected_export_ids = $("#select_export").val() || '';
                },
            },
            columns: [
                {
                    data: null,
                    name: 'serial_number',
                    title: 'S.No',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { data: 'user_name', name: 'users.name', title: 'User' }, // Change to users.name
                { data: 'file_name_raw', name: 'file_name', title: 'File Name' },
                { data: 'file_size_formatted', name: 'file_size', title: 'Size' },
                { data: 'created_at', name: 'created_at', title: 'Generated At' },
                { data: 'expires_at', name: 'expires_at', title: 'Expires At' },
                { data: 'action', name: 'action', orderable: false, searchable: false, title: 'Actions' }
            ],
            drawCallback: function(settings) {
                reInitBootstrapTooltip();
            }
        });
    }

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(event) {
        let tabType = $(event.target).data('tab-type');

        if (tabType === "export-schedule") {
            exportTable.ajax.reload();
        } else if (tabType === "export-history") {
            exportHistoryTable.ajax.reload();
            $('#select_export').select2({
                placeholder: "Select Export Setting",
                allowClear: true
            });
        }
    });

    $(document).on('click', '.export-details', function() {
        var id = $(this).data('id'); // Get the title from the clicked element

        $('#preloader').show();
        $.ajax({
            url: "{{ route('export.schedule.details') }}",
            type: 'GET',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(response) {
                $('#preloader').hide();
                if (response.success && response.data) {
                    let export_data = response.data;
                    $("#export-leads-model").attr('export-id', export_data?.id);
                    $("#export-leads-model #export_user_name").text(export_data.user?.name || "N/A");
                    $("#export-leads-model #export_title").text(export_data.title || "N/A");
                    $("#export-leads-model #export_description").text(export_data.description || "N/A");
                    $("#export-leads-model #export_file_prefix").text(export_data.file_prefix || "N/A");
                    $("#export-leads-model #export_export_formats").text(export_data.export_formats?.join(", ") || "N/A");

                    // $("#export-leads-model #export_filters").text(export_data.filters || "N/A");

                    let filter_data_content = "";

                    // Search Value
                    if (export_data.filters_data?.search_value?.trim()) {
                        filter_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Search Value: </label>
                                <span>${export_data.filters_data.search_value}</span>
                            </div>`;
                    }

                    // Source Sites
                    if (export_data.filters_data?.source_sites?.trim()) {
                        filter_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Source Sites: </label>
                                <span>${export_data.filters_data.source_sites}</span>
                            </div>`;
                    }

                    // Campaign List IDs
                    if (export_data.filters_data?.campaign_list?.trim()) {
                        filter_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Campaign List Ids: </label>
                                <span>${export_data.filters_data.campaign_list}</span>
                            </div>`;
                    }

                    // Tax Debt Amount
                    if (export_data.filters_data?.tax_debt_amount?.operator && export_data.filters_data?.tax_debt_amount?.value) {
                        filter_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Tax Debt Amount: </label>
                                <span>${getOperatorName(export_data.filters_data.tax_debt_amount.operator)} ${export_data.filters_data.tax_debt_amount.value}</span>
                            </div>`;
                    }

                    // CC Debt Amount
                    if (export_data.filters_data?.cc_debt_amount?.operator && export_data.filters_data?.cc_debt_amount?.value) {
                        filter_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">CC Debt Amount: </label>
                                <span>${getOperatorName(export_data.filters_data.cc_debt_amount.operator)} ${export_data.filters_data.cc_debt_amount.value}</span>
                            </div>`;
                    }

                    $("#export-leads-model #export_filters").html(filter_data_content);

                    //  Start; Additional Data
                    let additional_data_content = "";

                    // Sort By
                    if (export_data.additional_data?.sort_by?.field && export_data.additional_data?.sort_by?.sorting_order) {
                        additional_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Sort By: </label>
                                <span>Field: ${export_data.additional_data.sort_by.field}, Order: ${export_data.additional_data.sort_by.sorting_order}</span>
                            </div>`;
                    }

                    // Export in Batches
                    if (export_data.additional_data?.export_in_batches) {
                        additional_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Export in Batches: </label>
                                <span>Yes</span>
                            </div>`;
                    } else {
                        additional_data_content += `
                            <div class="mt-1">
                                <label class="text-secondary">Export in Batches: </label>
                                <span>No</span>
                            </div>`;
                    }

                    // Set the HTML content
                    $("#export-leads-model #export_additional_data").html(additional_data_content);
                    //  End: Additional Data

                    $("#export-leads-model #export_columns").text(export_data.columns?.join(", ") || "N/A");

                    // Displaying Frequency
                    const formattedTime = export_data?.time ? ` at ${export_data?.time}` : "";
                    const formattedDay = export_data?.day_of_week ? ` on ${formatString(export_data?.day_of_week)}` : "";
                    const formattedDate = export_data?.day_of_month ? ` on the ${getOrdinalSuffix(export_data?.day_of_month)}` : "";

                    const tooltips = {
                        one_time: "Runs only once",
                        daily: "Runs every day" + formattedTime,
                        weekly: `Runs weekly${formattedDay}${formattedTime}`,
                        monthly: `Runs monthly${formattedDate}${formattedTime}`,
                        custom: "Runs on a custom schedule"
                    };

                    let frequency = `<span class="badge bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="${tooltips[export_data?.frequency] || 'Unknown frequency'}">${formatString(export_data?.frequency)}</span>`;

                    $("#export-leads-model #export_frequency").html(frequency);


                    $("#export-leads-model #export_time").text(export_data?.time || "N/A");
                    $("#export-leads-model #export_day_of_week").text(formatString(export_data?.day_of_week) || "N/A");
                    $("#export-leads-model #export_day_of_month").text(getOrdinalSuffix(export_data?.day_of_month) || "N/A");

                    if (export_data && export_data.frequency) {

                        $("#export-leads-model")
                        .find("#export_frequency_details_time_container, #export_frequency_details_day_of_week_container, #export_frequency_details_day_of_month_container")
                        .show();

                        if (export_data.frequency == 'one_time') {
                            $("#export-leads-model #export_frequency_details_time_container").hide();
                            $("#export-leads-model #export_frequency_details_day_of_week_container").hide();
                            $("#export-leads-model #export_frequency_details_day_of_month_container").hide();
                        } else if (export_data.frequency == 'daily') {
                            $("#export-leads-model #export_frequency_details_day_of_week_container").hide();
                            $("#export-leads-model #export_frequency_details_day_of_month_container").hide();
                        } else if (export_data.frequency == 'weekly') {
                            $("#export-leads-model #export_frequency_details_day_of_month_container").hide();
                        } else if (export_data.frequency == 'monthly') {
                            $("#export-leads-model #export_frequency_details_day_of_week_container").hide();
                        }
                    }

                    // End Displaying Frequency
                    $("#export-leads-model #export_next_run_at").text(
                        export_data.next_run_at
                            ? moment(export_data.next_run_at).tz(appTimezone).format('YYYY-MM-DD HH:mm:ss')
                            : "N/A"
                    );

                    $("#export-leads-model #export_last_run_at").text(
                        export_data.last_run_at
                            ? moment(export_data.last_run_at).tz(appTimezone).format('YYYY-MM-DD HH:mm:ss')
                            : "N/A"
                    );

                    // Apply status badge
                    const statusClasses = {
                        active: 'bg-success',
                        paused: 'bg-warning',
                        stopped: 'bg-danger'
                    };
                    let statusClass = statusClasses[export_data.status?.toLowerCase()] || 'bg-secondary';
                    $("#export-leads-model #export_status").val(export_data?.status);

                    // Apply running status badge
                    const runingStatusClasses = {
                        scheduled: 'bg-primary',
                        success: 'bg-success',
                        failed: 'bg-danger',
                        pending: 'bg-info',
                        paused: 'bg-warning',
                        stopped: 'bg-danger'
                    };
                    let runingStatusClass = runingStatusClasses[export_data.runing_status] || 'bg-secondary';
                    $("#export_runing_status").html(`<span class="badge ${runingStatusClass}">${export_data.runing_status}</span>`);
                    // Show modal after updating the content

                    let formattedStatus = export_data?.status
                        ? export_data.status.charAt(0).toUpperCase() + export_data.status.slice(1)
                        : 'Unknown';

                    if (export_data?.status === 'stopped' || export_data?.frequency === 'one_time') {
                        $("#export-leads-model .btn-update-export-schedule").hide();
                        $("#export-leads-model #export_status_badge")
                            .html(`<span class="badge ${statusClass}">${formattedStatus}</span>`)
                            .show();
                        $("#export-leads-model #export_status").hide();
                    } else {
                        $("#export-leads-model .btn-update-export-schedule").show();
                        $("#export-leads-model #export_status_badge").hide();
                        $("#export-leads-model #export_status").show();
                    }

                    $('#export-leads-model').modal('show');

                    reInitBootstrapTooltip();
                } else {
                    toastr.error("No data found for the selected title.");
                }
            },
            error: function() {
                $('#preloader').hide();
                toastr.error("Failed to fetch data. Please try again.");
            }
        });
    });

    $(document).on('click', '#closeExportModelBtn', function() {
        $('#export-leads-model').modal('hide');
    });

    $(document).on('click', '.delete-export-file', function () {
        let exportFileId = $(this).data('id');

        Swal.fire({
            title: "Are you sure?",
            html: `
                <div style="font-size: 16px; color: #555;">
                    <p style="margin-bottom: 10px;">
                        <strong>Have you downloaded this file?</strong>
                    </p>
                    <p style="margin-bottom: 10px;">
                        Once deleted, this file will be <strong>permanently removed</strong> and <span style="color: #d33;">cannot be recovered.</span>
                    </p>
                    <p style="font-size: 14px; color: #888;">
                        If you still need this file, please download it before proceeding.
                    </p>
                </div>
            `,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: '<i class="bx bx-trash"></i> Yes, delete it!',
            cancelButtonText: '<i class="bx bx-x"></i> No, keep it',
            customClass: {
                popup: 'swal2-popup-custom'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('export.export-files.delete', ':id') }}`.replace(':id', exportFileId),
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Deleted!",
                                text: "The file has been permanently removed.",
                                icon: "success",
                                confirmButtonColor: "#3085d6"
                            });
                            exportHistoryTable.ajax.reload(); // Refresh DataTable
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: response.message,
                                icon: "error",
                                confirmButtonColor: "#d33"
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: "Oops!",
                            text: "Something went wrong. Try again.",
                            icon: "error",
                            confirmButtonColor: "#d33"
                        });
                    }
                });
            }
        });
    });

    configureExportSelect();

    const reloadExportHistoryTable = () => {
        exportHistoryTable.ajax.reload();
    }

    $('.export-history .btn-filter').click(function() {
        var selected_export_ids = $("#select_export").val();
        reloadExportHistoryTable();
    });

    $('.export-history .btn-clear-filter').click(function() {
        $("#select_export").val(null).trigger('change');
        reloadExportHistoryTable();
    });

    $(document).on('click', '.export-specific-files', function() {
        var export_id = $(this).data('id'); // Get the title from the clicked element
        $("#select_export").val([export_id]).trigger('change');
        $('button[data-tab-type="export-history"]').tab('show');
    });

    $(document).on('click', '.btn-update-export-schedule', function() {
        let export_status = $("#export-leads-model #export_status").val();
        let export_id = $("#export-leads-model").attr('export-id');

        if (!export_id || !export_status) {
            Swal.fire({
                icon: "warning",
                title: "Incomplete Information",
                text: "Please select an export status before updating. The update cannot proceed without this information.",
                customClass: { popup: 'swal2-modal' }
            });
            return;
        }

        Swal.fire({
            title: "Confirm Status Update",
            text: "Are you sure you want to update the export schedule status? This change will be applied immediately.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Update",
            cancelButtonText: "No, Cancel",
            customClass: { popup: 'swal2-modal' },
            didOpen: () => {
                document.querySelector('.swal2-container').style.zIndex = '9999';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('export.schedule.status-update') }}",
                    type: "POST",
                    data: {
                        export_id: export_id,
                        status: export_status,
                        _token: "{{ csrf_token() }}"
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: "Updating Status...",
                            text: "Please wait while we process your request.",
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => { Swal.showLoading(); },
                            customClass: { popup: 'swal2-modal' },
                            didOpen: () => {
                                document.querySelector('.swal2-container').style.zIndex = '9999';
                            }
                        });
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: "success",
                            title: "Update Successful",
                            text: "The export schedule status has been updated successfully!",
                            customClass: { popup: 'swal2-modal' },
                            didOpen: () => {
                                document.querySelector('.swal2-container').style.zIndex = '9999';
                            }
                        }).then(() => {
                            $("#export-leads-model").modal('hide');
                            exportTable.ajax.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = xhr.responseJSON?.message || "An error occurred while updating. Please try again later.";
                        Swal.fire({
                            icon: "error",
                            title: "Update Failed",
                            text: errorMessage,
                            customClass: { popup: 'swal2-modal' },
                            didOpen: () => {
                                document.querySelector('.swal2-container').style.zIndex = '9999';
                            }
                        });
                    }
                });
            }
        });
    });

    function deleteExportByExportId(export_id) {
        // Use SweetAlert to confirm deletion
        Swal.fire({
            title: 'Are you sure?',
            html: `
                <div style="font-size: 16px; color: #555;">
                    <p style="margin-bottom: 10px;">
                        <strong>This action will delete the entire export schedule.</strong>
                    </p>
                    <p style="margin-bottom: 10px;">
                        Not only will the export be deleted, but <span style="color: #d33;">all associated history files will also be permanently removed</span>.
                    </p>
                    <p style="font-size: 14px; color: #888;">
                        <strong>Once deleted, these files cannot be recovered.</strong> If you need the files, please download them before proceeding.
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="bx bx-trash"></i> Yes, delete it!',
            cancelButtonText: '<i class="bx bx-x"></i> No, keep it',
            customClass: { popup: 'swal2-modal' },
            didOpen: () => {
                document.querySelector('.swal2-container').style.zIndex = '9999';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with the deletion if confirmed
                $.ajax({
                    url: "{{ route('delete.export.schedule') }}",  // Named route for delete
                    type: 'POST',
                    data: {
                        export_id: export_id,  // Send export ID in the request body
                        _token: "{{ csrf_token() }}"  // CSRF token for security
                    },
                    beforeSend: function() {
                        // Show loading spinner or message before making the request
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait while we delete the export schedule and its associated files.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => { Swal.showLoading(); },
                            customClass: { popup: 'swal2-modal' },
                            didOpen: () => {
                                document.querySelector('.swal2-container').style.zIndex = '9999';
                            }
                        });
                    },
                    success: function(response) {
                        // Show success message with SweetAlert
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'The export schedule and all associated files have been permanently deleted.',
                            customClass: { popup: 'swal2-modal' },
                            didOpen: () => {
                                document.querySelector('.swal2-container').style.zIndex = '9999';
                            }
                        }).then(() => {
                            $("#export-leads-model").modal('hide');
                            exportTable.ajax.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        // Show error message with SweetAlert
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'There was a problem deleting the export schedule and its files. Please try again.',
                            customClass: { popup: 'swal2-modal' },
                            didOpen: () => {
                                document.querySelector('.swal2-container').style.zIndex = '9999';
                            }
                        });
                    }
                });
            }
        });
    }

    $(document).on('click', '.btn-delete-export-schedule', function() {
        let export_id = $("#export-leads-model").attr('export-id');
        deleteExportByExportId(export_id);
    });

    $(document).on('click', '.delete-export', function() {
        var export_id = $(this).data('id');
        deleteExportByExportId(export_id);
    });
});
</script>
@endsection