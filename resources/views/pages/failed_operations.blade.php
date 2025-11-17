@extends('layout.master')

@section('page-title', config('app.name') . ' - Failed Operations')
@section('custom-page-style')
<style>
    .job-error {
        word-wrap: break-word;
    }

    .no-wrap {
        /* white-space: nowrap !important; */
        min-width: 165px !important;
    }

    .tab-content .full-text {
        word-break: break-word;
    }
</style>
@endsection
@section('page-content')

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="nav-align-top mb-4">
                <ul class="nav nav-pills mb-3 nav-fill" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'failed-jobs' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#failed-jobs-tab" aria-controls="failed-jobs-tab"
                            aria-selected="{{ $activeTab == 'failed-jobs' ? 'true' : 'false' }}" data-tab-type="failed-jobs">
                            <i class="bx bx-x-circle me-1"></i>
                            Failed Jobs
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'system-logs' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#system-logs-tab" aria-controls="system-logs-tab"
                            aria-selected="{{ $activeTab == 'system-logs' ? 'true' : 'false' }}" data-tab-type="system-logs">
                            <i class="bx bx-file-find me-1"></i>
                            System Logs
                        </button>
                    </li>
                    <!-- New Tab: System Failed Logs -->
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'system-failed-logs' ? 'active' : '' }}" role="tab"
                            data-bs-toggle="tab" data-bs-target="#system-failed-logs-tab" aria-controls="system-failed-logs-tab"
                            aria-selected="{{ $activeTab == 'system-failed-logs' ? 'true' : 'false' }}" data-tab-type="system-failed-logs">
                            <i class="bx bx-error-alt me-1"></i>
                            System Failed Logs
                        </button>
                    </li>
                    <!-- New Tab: Export Logs -->
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'export-logs' ? 'active' : '' }}" role="tab"
                            data-bs-toggle="tab" data-bs-target="#export-logs-tab" aria-controls="export-logs-tab"
                            aria-selected="{{ $activeTab == 'export-logs' ? 'true' : 'false' }}" data-tab-type="export-logs">
                            <i class="bx bx-download me-1"></i>
                            Export Logs
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $activeTab == 'failed-dispatch' ? 'active' : '' }}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#failed-dispatch-tab" aria-controls="failed-dispatch-tab"
                            aria-selected="{{ $activeTab == 'failed-dispatch' ? 'true' : 'false' }}" data-tab-type="failed-dispatch">
                            <i class="bx bx-file-find me-1"></i>
                            Failed Dispatch Leads
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade {{ $activeTab == 'failed-jobs' ? 'show active' : '' }}" id="failed-jobs-tab" role="tabpanel">
                        <div class="card-header export_schedule">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fs-3 fw-bolder card-title text-primary">Failed Jobs</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped mt-3" id="failed-jobs-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>UUID</th>
                                        <th>Connection</th>
                                        <th>Queue</th>
                                        <th>Failed At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $activeTab == 'system-logs' ? 'show active' : '' }}" id="system-logs-tab" role="tabpanel">
                        <div class="card-header system-logs">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="fs-3 fw-bolder card-title text-primary">System Logs</h5>
                                        </div>
                                        <div class="col-2">
                                            <label>Select Log File</label>
                                            <select id="logFileSelector" class="form-control">
                                                @if (!empty($syatemLogFiles))
                                                    @foreach ($syatemLogFiles as $file)
                                                        <option value="{{ $file }}" {{ $loop->first ? 'selected' : '' }}>
                                                            {{ basename($file) }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option disabled>No log files available</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <label>Select Log Type</label>
                                            <select id="logTypeSelector" class="form-control select2" multiple>
                                                <option value="INFO">INFO</option>
                                                <option value="ERROR">Error</option>
                                                <option value="CRITICAL">Critical</option>
                                                <option value="WARNING">Warning</option>
                                                <option value="NOTICE">Notice</option>
                                                <option value="DEBUG">Debug</option>
                                            </select>
                                        </div>
                                        <div class="col-1 mt-4">
                                            <!-- Download Button -->
                                            <button type="button" class="btn btn-primary btn-sm me-2 btn-download-log-file" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-log-file" btn-sm btn-delete-log-file" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="system-logs-table">
                                <thead>
                                    <tr>
                                        <th>Sr. No</th>
                                        <th>Created At</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $activeTab == 'failed-dispatch' ? 'show active' : '' }}" id="failed-dispatch-tab" role="tabpanel">
                        <div class="card-header failed-dispatch">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12">
                                            <h5 class="fs-3 fw-bolder card-title text-primary">Failed Dispatch Leads</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped mt-3" id="failed-dispatch-table">
                                <thead>
                                    <tr>
                                        <th>Sr. No</th>
                                        <th>Error Message</th>
                                        <th>Client IP</th>
                                        <th>User Agent</th>
                                        <th>Request URL</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <!-- System Failed Logs Tab Content -->
                    <div class="tab-pane fade {{ $activeTab == 'system-failed-logs' ? 'show active' : '' }}" id="system-failed-logs-tab" role="tabpanel">
                        <div class="card-header system-failed-logs">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="fs-3 fw-bolder card-title text-primary">System Failed Logs</h5>
                                        </div>
                                        <div class="col-2">
                                            <label>Select Log File</label>
                                            <select id="systemFailedLogFileSelector" class="form-control">
                                                @if (!empty($systemFailedLogFiles))
                                                    @foreach ($systemFailedLogFiles as $file)
                                                        <option value="{{ $file }}" {{ $loop->first ? 'selected' : '' }}>
                                                            {{ basename($file) }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option disabled>No log files available</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <label>Select Log Type</label>
                                            <select id="systemFailedLogTypeSelector" class="form-control select2" multiple>
                                                <option value="INFO">INFO</option>
                                                <option value="ERROR">Error</option>
                                                <option value="CRITICAL">Critical</option>
                                                <option value="WARNING">Warning</option>
                                                <option value="NOTICE">Notice</option>
                                                <option value="DEBUG">Debug</option>
                                            </select>
                                        </div>
                                        <div class="col-1 mt-4">
                                            <!-- Download Button -->
                                            <button type="button" class="btn btn-primary btn-sm me-2 btn-download-log-file" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-log-file" btn-sm btn-delete-log-file" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped mt-3" id="system-failed-logs-table">
                                <thead>
                                    <tr>
                                        <th>Sr. No</th>
                                        <th>Created At</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <!-- Export Logs Tab Content -->
                    <div class="tab-pane fade {{ $activeTab == 'export-logs' ? 'show active' : '' }}" id="export-logs-tab" role="tabpanel">
                        <div class="card-header export-logs">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="fs-3 fw-bolder card-title text-primary">Export Logs</h5>
                                        </div>
                                        <div class="col-2">
                                            <label>Select Log File</label>
                                            <select id="exportLogFileSelector" class="form-control">
                                                @if (!empty($exportLogFiles))
                                                    @foreach ($exportLogFiles as $file)
                                                        <option value="{{ $file }}" {{ $loop->first ? 'selected' : '' }}>
                                                            {{ basename($file) }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option disabled>No log files available</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <label>Select Log Type</label>
                                            <select id="exportLogTypeSelector" class="form-control select2" multiple>
                                                <option value="INFO">INFO</option>
                                                <option value="ERROR">Error</option>
                                                <option value="CRITICAL">Critical</option>
                                                <option value="WARNING">Warning</option>
                                                <option value="NOTICE">Notice</option>
                                                <option value="DEBUG">Debug</option>
                                            </select>
                                        </div>
                                        <div class="col-1 mt-4">
                                            <!-- Download Button -->
                                            <button type="button" class="btn btn-primary btn-sm me-2 btn-download-log-file" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-log-file" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped mt-3" id="export-logs-table">
                                <thead>
                                    <tr>
                                        <th>Sr. No</th>
                                        <th>Created At</th>
                                        <th>Message</th>
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

<!-- Job Details Modal -->
<div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border border-bottom-4">
                <h5 class="modal-title fw-bold" id="jobDetailsModalLabel">Failed Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalJobDetails"></div> <!-- Job details will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Failed To Dispatch Lead Details Modal -->
<div class="modal fade" id="failedToDispatchLeadsModal" tabindex="-1" aria-labelledby="failedToDispatchLeadsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border border-bottom-4">
                <h5 class="modal-title fw-bold" id="failedToDispatchLeadsModalLabel">Failed To Dispatch Lead Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="failedToDispatchLeadsModalDetails"></div> <!-- Job details will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
<script type="text/javascript" src="{{ asset('vendor/js/daterangepicker.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/select2.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery-ui.min.js') }}?v={{ currentVersion() }}"></script>
<script>
$(document).ready(function () {
    $('.menu-item').removeClass('active');
    $('.menu-item-failed-operations').addClass('active');

    $('#logTypeSelector, #systemFailedLogTypeSelector, #exportLogTypeSelector').select2({
        width: '100%',
        placeholder: "Select Log Types",
        allowClear: true
    });

    // Ensure all options in logTypeSelector are selected by default
    $('#logTypeSelector').val($('#logTypeSelector').find('option').map(function () {
        return $(this).val();
    }).get()).trigger('change');

    // Ensure all options in systemFailedLogTypeSelector are selected by default
    $('#systemFailedLogTypeSelector').val($('#systemFailedLogTypeSelector').find('option').map(function () {
        return $(this).val();
    }).get()).trigger('change');

    // Ensure all options in exportLogTypeSelector are selected by default
    $('#exportLogTypeSelector').val($('#exportLogTypeSelector').find('option').map(function () {
        return $(this).val();
    }).get()).trigger('change');

    let activeTab = "{{ $activeTab }}";
    let jobsTable, logsTable, dispatchTable, systemFailedLogsTable, exportLogsTable;
    const MAX_CHARACTER_VIEW_LENGHT = 200;

    // Initialize DataTables based on active tab
    if (activeTab === "failed-jobs") {
        jobsTable = $('#failed-jobs-table').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            ajax: '{{ route("failed-operations.failed-jobs.list-data") }}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'uuid', name: 'uuid' },
                { data: 'connection', name: 'connection' },
                { data: 'queue', name: 'queue' },
                { data: 'failed_at', name: 'failed_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });
    } else if (activeTab === "system-logs") {
        logsTable = $('#system-logs-table').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            deferRender: true, // Improves performance by delaying rendering
            ajax: {
                url: '{{ route("failed-operations.failed-logs.list") }}',
                data: function (d) {
                    d.log_file = $('#logFileSelector').val() || '';
                    d.log_types = $('#logTypeSelector').val() || [];
                },
                error: function (xhr) {
                    let message = "An error occurred while loading data.";

                    try {
                        let response = JSON.parse(xhr.responseText);
                        message = response.error || response.message || message;
                    } catch (e) {
                        console.error("Error parsing server response:", e);
                    }

                    toastr.error(message, "Server Error", { timeOut: 2000 });
                }
            },
            columns: [
                {
                    data: null,
                    title: 'Sr. No',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: "created_at",
                    title: "Created At",
                    render: function (data) {
                        return `<span>${data}</span>`;
                    }
                },
                {
                    data: "message",
                    title: "Log Message",
                    render: function (data, type, row) {
                        let maxLength = MAX_CHARACTER_VIEW_LENGHT; // Limit characters for preview
                        if (data.length > maxLength) {
                            let shortText = data.substring(0, maxLength) + '...';
                            return `
                                <span class="short-text">${shortText}</span>
                                <span class="full-text d-none">${data}</span>
                                <a href="#" class="view-more text-primary" style="display: block; margin-top: 5px;">View More</a>
                            `;
                        } else {
                            return `<span>${data}</span>`;
                        }
                    }
                }
            ],
            order: [[1, 'desc']],
            createdRow: function (row, data, dataIndex) {
                applyRowBackground(row, data.message); // Apply Bootstrap background color
            },
            drawCallback: function () {
                $('#system-logs-table tbody').off('click', '.view-more').on('click', '.view-more', function (e) {
                    e.preventDefault();
                    let $this = $(this);
                    let row = $this.closest('td');

                    if ($this.text() === "View More") {
                        row.find('.short-text').addClass('d-none');
                        row.find('.full-text').removeClass('d-none');
                        $this.text("View Less");
                    } else {
                        row.find('.short-text').removeClass('d-none');
                        row.find('.full-text').addClass('d-none');
                        $this.text("View More");
                    }
                });
            }
        });
    } else if (activeTab === "failed-dispatch") {
        dispatchTable = $('#failed-dispatch-table').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            ajax: '{{ route("failed-operations.failed-dispatch.list") }}',
            columns: [
                {
                    data: null,
                    title: 'Sr. No',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'error_message',
                    name: 'error_message',
                    title: 'Error Message',
                    render: function(data) {
                        if (!data) return '';
                        return data.length > 50 ? data.substring(0, 50) + '...' : data;
                    }
                },
                { data: 'client_ip', name: 'client_ip', title: 'Client IP' },
                { data: 'user_agent', name: 'user_agent', title: 'User Agent', orderable: false, searchable: false },
                { data: 'request_url', name: 'request_url', title: 'Request URL' },
                {
                    data: 'created_at',
                    name: 'created_at',
                    title: 'Created At',
                    render: function (data) {
                        return `<span>${new Date(data).toLocaleString()}</span>`;
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    title: 'Action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[6, 'desc']] // Order by Created At (second column) descending
        });
    } else if (activeTab === "system-failed-logs") {
        systemFailedLogsTable = $('#system-failed-logs-table').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            deferRender: true, // Improves performance by delaying rendering
            ajax: {
                url: '{{ route("failed-operations.system-failed-logs.list") }}',
                data: function (d) {
                    d.log_file = $('#systemFailedLogFileSelector').val() || '';
                    d.log_types = $('#systemFailedLogTypeSelector').val() || [];
                },
                error: function (xhr) {
                    let message = "An error occurred while loading data.";

                    try {
                        let response = JSON.parse(xhr.responseText);
                        message = response.error || response.message || message;
                    } catch (e) {
                        console.error("Error parsing server response:", e);
                    }

                    toastr.error(message, "Server Error", { timeOut: 2000 });
                }
            },
            columns: [
                {
                    data: null,
                    title: 'Sr. No',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: "created_at",
                    title: "Created At",
                    render: function (data) {
                        return `<span>${data}</span>`;
                    }
                },
                {
                    data: "message",
                    title: "Log Message",
                    render: function (data, type, row) {
                        let maxLength = MAX_CHARACTER_VIEW_LENGHT; // Limit characters for preview
                        if (data.length > maxLength) {
                            let shortText = data.substring(0, maxLength) + '...';
                            return `
                                <span class="short-text">${shortText}</span>
                                <span class="full-text d-none">${data}</span>
                                <a href="#" class="view-more text-primary" style="display: block; margin-top: 5px;">View More</a>
                            `;
                        } else {
                            return `<span>${data}</span>`;
                        }
                    }
                }
            ],
            order: [[1, 'desc']],
            createdRow: function (row, data, dataIndex) {
                applyRowBackground(row, data.message); // Reuse your background coloring function
            },
            drawCallback: function () {
                $('#system-failed-logs-table tbody')
                    .off('click', '.view-more')
                    .on('click', '.view-more', function (e) {
                        e.preventDefault();
                        let $this = $(this);
                        let row = $this.closest('td');

                        if ($this.text() === "View More") {
                            row.find('.short-text').addClass('d-none');
                            row.find('.full-text').removeClass('d-none');
                            $this.text("View Less");
                        } else {
                            row.find('.short-text').removeClass('d-none');
                            row.find('.full-text').addClass('d-none');
                            $this.text("View More");
                        }
                    });
            }
        });
    } else if (activeTab === "export-logs") {
        exportLogsTable = $('#export-logs-table').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            deferRender: true, // Improves performance by delaying rendering
            ajax: {
                url: '{{ route("failed-operations.export-logs.list") }}',
                data: function (d) {
                    d.log_file = $('#exportLogFileSelector').val() || '';
                    d.log_types = $('#exportLogTypeSelector').val() || [];
                },
                error: function (xhr) {
                    let message = "An error occurred while loading data.";

                    try {
                        let response = JSON.parse(xhr.responseText);
                        message = response.error || response.message || message;
                    } catch (e) {
                        console.error("Error parsing server response:", e);
                    }

                    toastr.error(message, "Server Error", { timeOut: 2000 });
                }
            },
            columns: [
                {
                    data: null,
                    title: 'Sr. No',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: "created_at",
                    title: "Created At",
                    render: function (data) {
                        return `<span>${data}</span>`;
                    }
                },
                {
                    data: "message",
                    title: "Log Message",
                    render: function (data, type, row) {
                        let maxLength = MAX_CHARACTER_VIEW_LENGHT; // Limit characters for preview
                        if (data.length > maxLength) {
                            let shortText = data.substring(0, maxLength) + '...';
                            return `
                                <span class="short-text">${shortText}</span>
                                <span class="full-text d-none">${data}</span>
                                <a href="#" class="view-more text-primary" style="display: block; margin-top: 5px;">View More</a>
                            `;
                        } else {
                            return `<span>${data}</span>`;
                        }
                    }
                }
            ],
            order: [[1, 'desc']],
            createdRow: function (row, data, dataIndex) {
                applyRowBackground(row, data.message); // Apply Bootstrap background color
            },
            drawCallback: function () {
                $('#export-logs-table tbody')
                    .off('click', '.view-more')
                    .on('click', '.view-more', function (e) {
                        e.preventDefault();
                        let $this = $(this);
                        let row = $this.closest('td');

                        if ($this.text() === "View More") {
                            row.find('.short-text').addClass('d-none');
                            row.find('.full-text').removeClass('d-none');
                            $this.text("View Less");
                        } else {
                            row.find('.short-text').removeClass('d-none');
                            row.find('.full-text').addClass('d-none');
                            $this.text("View More");
                        }
                    });
            }
        });
    }

    // View Job Data
    $(document).on('click', '.view-job', function () {
        let jobId = $(this).data('id');

        $('#preloader').show();
        $.ajax({
            url: `{{ route('failed-operations.failed-job.get-data-by-id') }}`,
            method: 'POST',
            data: { id: jobId, _token: '{{ csrf_token() }}' },
            success: function (response) {
                $('#preloader').hide();
                let exceptionText = response.exception || "No error details available";
                let shortException = exceptionText.substring(0, 200);
                let isLong = exceptionText.length > 200;

                let jobDetails = `
                    <p><strong>Job ID:</strong> <span class="text-primary">${response.id}</span></p>
                    <p><strong>UUID:</strong> <span class="text-muted">${response.uuid}</span>
                        <button type="button" class="btn btn-sm px-2" onclick="copyToClipboard('${response.uuid}')">
                            <i class="menu-icon tf-icons bx bx-copy text-primary"></i>
                        </button>
                    </p>
                    <p><strong>Connection:</strong> <span class="text-info">${response.connection}</span></p>
                    <p><strong>Queue:</strong> <span class="text-info">${response.queue}</span></p>
                    <p><strong>Failed At:</strong> <span class="text-danger">${new Date(response.failed_at).toLocaleString()}</span></p>
                    <p><strong>Error Message:</strong> 
                        <span class="text-danger job-error" id="errorMessage">${shortException}${isLong ? '...' : ''}</span>
                        ${isLong ? `
                            <a href="#"
                            class="ms-2 text-decoration-underline text-primary view-more"
                            data-full="${encodeURIComponent(exceptionText)}"
                            data-short="${encodeURIComponent(shortException)}"
                            data-is-long="${isLong}">View More</a>` : ''}
                    </p>
                    <p><strong>Payload:</strong></p>
                    <pre class="bg-light border p-2 text-success">${JSON.stringify(JSON.parse(response.payload), null, 2)}</pre>
                `;

                $('#modalJobDetails').html(jobDetails);
                $('#jobDetailsModal').modal('show');
            },
            error: function () {
                $('#preloader').hide();
                toastr.error('Failed to fetch job details.', 'Error', { timeOut: 2000 });
            }
        });
    });

    $('#modalJobDetails').on('click', '.view-more', function (e) {
        e.preventDefault();

        let $this = $(this);
        let $errorMessage = $('#modalJobDetails').find('#errorMessage');

        let full = decodeURIComponent($this.data('full'));
        let short = decodeURIComponent($this.data('short'));
        let isLong = $this.data('is-long');

        if ($this.text().trim() === "View More") {
            $errorMessage.text(full);
            $this.text("View Less");
        } else {
            $errorMessage.text(short + (isLong ? '...' : ''));
            $this.text("View More");
        }
    });

    // Retry Job Click Event
    $(document).on('click', '.retry-job', function () {
        let jobId = $(this).data('id');

        Swal.fire({
            title: "Retry Failed Job?",
            text: "This will attempt to process the failed job again.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Retry",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('failed-operations.failed-jobs.retry') }}", { id: jobId, _token: "{{ csrf_token() }}" })
                    .done(function (response) {
                        Swal.fire("Success", response.success, "success");
                        if (jobsTable) jobsTable.ajax.reload();
                    })
                    .fail(function (xhr) {
                        Swal.fire("Error", xhr.responseJSON.error, "error");
                    });
            }
        });
    });

   // Delete Job Click Event
    $(document).on('click', '.delete-job', function () {
        let jobId = $(this).data('id');

        Swal.fire({
            title: "Are you sure you want to delete this job?",
            text: "This action is irreversible and the job will be permanently removed from the failed jobs list. Please make sure this job is no longer important before proceeding.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it",
            cancelButtonText: "No, keep it"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('failed-operations.failed-jobs.delete') }}",
                    type: "POST",
                    data: {
                        id: jobId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        Swal.fire("Deleted!", response.message, "success");
                        if (jobsTable) jobsTable.ajax.reload();
                    },
                    error: function (xhr) {
                        Swal.fire("Error", xhr.responseJSON?.error || "Something went wrong", "error");
                    }
                });
            }
        });
    });

    // Reload DataTable on Tab Change
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(event) {
        let tabType = $(event.target).data('tab-type');
        if (tabType === "failed-jobs") {
            if (!$.fn.DataTable.isDataTable('#failed-jobs-table')) {
                jobsTable = $('#failed-jobs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    ajax: '{{ route("failed-operations.failed-jobs.list-data") }}',
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'uuid', name: 'uuid' },
                        { data: 'connection', name: 'connection' },
                        { data: 'queue', name: 'queue' },
                        { data: 'failed_at', name: 'failed_at' },
                        { data: 'action', name: 'action', orderable: false, searchable: false }
                    ],
                    order: [[0, 'desc']]
                });
            } else {
                $('#failed-jobs-table').DataTable().ajax.reload();
            }
        } else if (tabType === "system-logs") {
            if (!$.fn.DataTable.isDataTable('#system-logs-table')) {
                logsTable = $('#system-logs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    deferRender: true, // Improves performance by delaying rendering
                    ajax: {
                        url: '{{ route("failed-operations.failed-logs.list") }}',
                        data: function (d) {
                            d.log_file = $('#logFileSelector').val() || '';
                            d.log_types = $('#logTypeSelector').val() || [];
                        },
                        error: function (xhr) {
                            let message = "An error occurred while loading data.";

                            try {
                                let response = JSON.parse(xhr.responseText);
                                message = response.error || response.message || message;
                            } catch (e) {
                                console.error("Error parsing server response:", e);
                            }

                            toastr.error(message, "Server Error", { timeOut: 2000 });
                        }
                    },
                    columns: [
                        {
                            data: null,
                            title: 'Sr. No',
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            data: "created_at",
                            title: "Created At",
                            render: function (data) {
                                return `<span>${data}</span>`;
                            }
                        },
                        {
                            data: "message",
                            title: "Log Message",
                            render: function (data, type, row) {
                                let maxLength = MAX_CHARACTER_VIEW_LENGHT; // Limit characters for preview
                                if (data.length > maxLength) {
                                    let shortText = data.substring(0, maxLength) + '...';
                                    return `
                                        <span class="short-text">${shortText}</span>
                                        <span class="full-text d-none">${data}</span>
                                        <a href="#" class="view-more text-primary" style="display: block; margin-top: 5px;">View More</a>
                                    `;
                                } else {
                                    return `<span>${data}</span>`;
                                }
                            }
                        }
                    ],
                    order: [[1, 'desc']],
                    createdRow: function (row, data, dataIndex) {
                        applyRowBackground(row, data.message); // Apply Bootstrap background color
                    },
                    drawCallback: function () {
                        // Attach event listener after table is drawn
                        $('#system-logs-table tbody').off('click', '.view-more').on('click', '.view-more', function (e) {
                            e.preventDefault();
                            let $this = $(this);
                            let row = $this.closest('td');

                            if ($this.text() === "View More") {
                                row.find('.short-text').addClass('d-none');
                                row.find('.full-text').removeClass('d-none');
                                $this.text("View Less");
                            } else {
                                row.find('.short-text').removeClass('d-none');
                                row.find('.full-text').addClass('d-none');
                                $this.text("View More");
                            }
                        });
                    },
                    columnDefs: [
                        {
                            // targets: 0, // Target "Created At" column
                            // className: "no-wrap", // Apply CSS class
                            // width: "100px", // Minimum fixed width
                        }
                    ]
                });
            } else {
                $('#system-logs-table').DataTable().ajax.reload();
            }
        } else if(tabType === "failed-dispatch") {
            if (!$.fn.DataTable.isDataTable('#failed-dispatch-table')) {
                dispatchTable = $('#failed-dispatch-table').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    ajax: '{{ route("failed-operations.failed-dispatch.list") }}',
                    columns: [
                        {
                            data: null,
                            title: 'Sr. No',
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            data: 'error_message',
                            name: 'error_message',
                            title: 'Error Message',
                            render: function(data) {
                                if (!data) return '';
                                return data.length > 50 ? data.substring(0, 50) + '...' : data;
                            }
                        },
                        { data: 'client_ip', name: 'client_ip', title: 'Client IP' },
                        { data: 'user_agent', name: 'user_agent', title: 'User Agent', orderable: false, searchable: false },
                        { data: 'request_url', name: 'request_url', title: 'Request URL' },
                        { // Created At column
                            data: 'created_at',
                            name: 'created_at',
                            title: 'Created At',
                            render: function (data) {
                                return `<span>${new Date(data).toLocaleString()}</span>`;
                            }
                        },
                        {
                            data: 'action',
                            name: 'action',
                            title: 'Action',
                            orderable: false,
                            searchable: false,
                            className: 'text-nowrap' // 👈 this prevents wrapping
                        }
                    ],
                    order: [[6, 'desc']] // Order by Created At (second column) descending
                });
            } else {
                $('#failed-dispatch-table').DataTable().ajax.reload();
            }
        } else if (tabType === "system-failed-logs") {
            if (!$.fn.DataTable.isDataTable('#system-failed-logs-table')) {
                systemFailedLogsTable = $('#system-failed-logs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    deferRender: true, // Improves performance by delaying rendering
                    ajax: {
                        url: '{{ route("failed-operations.system-failed-logs.list") }}',
                        data: function (d) {
                            d.log_file = $('#systemFailedLogFileSelector').val() || '';
                            d.log_types = $('#systemFailedLogTypeSelector').val() || [];
                        },
                        error: function (xhr) {
                            let message = "An error occurred while loading data.";

                            try {
                                let response = JSON.parse(xhr.responseText);
                                message = response.error || response.message || message;
                            } catch (e) {
                                console.error("Error parsing server response:", e);
                            }

                            toastr.error(message, "Server Error", { timeOut: 2000 });
                        }
                    },
                    columns: [
                        {
                            data: null,
                            title: 'Sr. No',
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            data: "created_at",
                            title: "Created At",
                            render: function (data) {
                                return `<span>${data}</span>`;
                            }
                        },
                        {
                            data: "message",
                            title: "Log Message",
                            render: function (data, type, row) {
                                let maxLength = MAX_CHARACTER_VIEW_LENGHT; // Limit characters for preview
                                if (data.length > maxLength) {
                                    let shortText = data.substring(0, maxLength) + '...';
                                    return `
                                        <span class="short-text">${shortText}</span>
                                        <span class="full-text d-none">${data}</span>
                                        <a href="#" class="view-more text-primary" style="display: block; margin-top: 5px;">View More</a>
                                    `;
                                } else {
                                    return `<span>${data}</span>`;
                                }
                            }
                        }
                    ],
                    order: [[1, 'desc']],
                    createdRow: function (row, data, dataIndex) {
                        applyRowBackground(row, data.message); // Reuse your background coloring function
                    },
                    drawCallback: function () {
                        $('#system-failed-logs-table tbody')
                            .off('click', '.view-more')
                            .on('click', '.view-more', function (e) {
                                e.preventDefault();
                                let $this = $(this);
                                let row = $this.closest('td');

                                if ($this.text() === "View More") {
                                    row.find('.short-text').addClass('d-none');
                                    row.find('.full-text').removeClass('d-none');
                                    $this.text("View Less");
                                } else {
                                    row.find('.short-text').removeClass('d-none');
                                    row.find('.full-text').addClass('d-none');
                                    $this.text("View More");
                                }
                            });
                    }
                });
            } else {
                $('#system-failed-logs-table').DataTable().ajax.reload();
            }
        } else if (tabType === "export-logs") {
            if (!$.fn.DataTable.isDataTable('#export-logs-table')) {
                exportLogsTable = $('#export-logs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    destroy: true,
                    deferRender: true, // Improves performance by delaying rendering
                    ajax: {
                        url: '{{ route("failed-operations.export-logs.list") }}',
                        data: function (d) {
                            d.log_file = $('#exportLogFileSelector').val() || '';
                            d.log_types = $('#exportLogTypeSelector').val() || [];
                        },
                        error: function (xhr) {
                            let message = "An error occurred while loading data.";

                            try {
                                let response = JSON.parse(xhr.responseText);
                                message = response.error || response.message || message;
                            } catch (e) {
                                console.error("Error parsing server response:", e);
                            }

                            toastr.error(message, "Server Error", { timeOut: 2000 });
                        }
                    },
                    columns: [
                        {
                            data: null,
                            title: 'Sr. No',
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            data: "created_at",
                            title: "Created At",
                            render: function (data) {
                                return `<span>${data}</span>`;
                            }
                        },
                        {
                            data: "message",
                            title: "Log Message",
                            render: function (data, type, row) {
                                let maxLength = MAX_CHARACTER_VIEW_LENGHT; // Limit characters for preview
                                if (data.length > maxLength) {
                                    let shortText = data.substring(0, maxLength) + '...';
                                    return `
                                        <span class="short-text">${shortText}</span>
                                        <span class="full-text d-none">${data}</span>
                                        <a href="#" class="view-more text-primary" style="display: block; margin-top: 5px;">View More</a>
                                    `;
                                } else {
                                    return `<span>${data}</span>`;
                                }
                            }
                        }
                    ],
                    order: [[1, 'desc']],
                    createdRow: function (row, data, dataIndex) {
                        applyRowBackground(row, data.message); // Apply Bootstrap background color
                    },
                    drawCallback: function () {
                        $('#export-logs-table tbody')
                            .off('click', '.view-more')
                            .on('click', '.view-more', function (e) {
                                e.preventDefault();
                                let $this = $(this);
                                let row = $this.closest('td');

                                if ($this.text() === "View More") {
                                    row.find('.short-text').addClass('d-none');
                                    row.find('.full-text').removeClass('d-none');
                                    $this.text("View Less");
                                } else {
                                    row.find('.short-text').removeClass('d-none');
                                    row.find('.full-text').addClass('d-none');
                                    $this.text("View More");
                                }
                            });
                    }
                });
            } else {
                $('#export-logs-table').DataTable().ajax.reload();
            }
        }
    });

    $(document).on("change", "#logFileSelector, #logTypeSelector", function () {
        logsTable.ajax.reload();
    });

    $(document).on("change", "#systemFailedLogFileSelector, #systemFailedLogTypeSelector", function () {
        systemFailedLogsTable.ajax.reload();
    });

    $(document).on("change", "#exportLogFileSelector, #exportLogTypeSelector", function () {
        exportLogsTable.ajax.reload();
    });

    // Function to apply Bootstrap 5 background colors to rows
    function applyRowBackground(row, message) {
        let colorMap = {
            'INFO': 'table-info',
            'ERROR': 'table-danger',    // Bootstrap 5 Red
            'CRITICAL': 'table-dark',   // Bootstrap 5 Dark Red
            'WARNING': 'table-warning', // Bootstrap 5 Yellow
            'NOTICE': 'table-info',     // Bootstrap 5 Blue
            'DEBUG': 'table-secondary'  // Bootstrap 5 Gray
        };

        for (let type in colorMap) {
            if (message.includes(type)) {
                $(row).addClass(colorMap[type]);
                break;
            }
        }
    }

    // View Failed To Dispatch Lead Data
    $(document).on('click', '.view-failed-to-dispatch-lead', function () {
        let leadId = $(this).data('id');

        $('#preloader').show();
        $.ajax({
            url: `{{ route('failed-operations.failed-dispatch.get-data') }}`,
            method: 'POST',
            data: { id: leadId, _token: '{{ csrf_token() }}' },
            success: function (response) {
                $('#preloader').hide();
                // Format the payload for better readability
                let formattedPayload = JSON.stringify(response.payload, null, 2);

                // Build HTML for lead details using Bootstrap styling (titles default, values colored)
                let leadDetails = `
                    <p><strong>ID:</strong> <span class="text-primary">${response.id}</span></p>
                    <p><strong>Error Message:</strong> <pre class="text-danger">${response.error_message}</pre></p>
                    <p><strong>Exception Code:</strong> <span class="text-warning">${response.exception_code}</span></p>
                    <p><strong>Exception File:</strong> <span class="text-info">${response.exception_file}</span></p>
                    <p><strong>Exception Line:</strong> <span class="text-info">${response.exception_line}</span></p>
                    <p><strong>Stack Trace:</strong><br/><pre class="bg-light border p-2 text-secondary">${response.stack_trace}</pre></p>
                    <p><strong>Client IP:</strong> <span class="text-muted">${response.client_ip}</span></p>
                    <p><strong>User Agent:</strong> <span class="text-muted">${response.user_agent}</span></p>
                    <p><strong>Request URL:</strong> <span class="text-muted">${response.request_url}</span></p>
                    <p><strong>Payload:</strong><br/><pre class="bg-light border p-2 text-success">${formattedPayload}</pre></p>
                `;

                $('#failedToDispatchLeadsModalDetails').html(leadDetails);
                $('#failedToDispatchLeadsModal').modal('show');
            },
            error: function () {
                $('#preloader').hide();
                toastr.error('Failed to fetch lead details.', 'Error', { timeOut: 2000 });
            }
        });
    });

    // Retry Failed To Dispatch Lead Action
    $(document).on('click', '.retry-failed-to-dispatch-lead', function () {
        let leadId = $(this).data('id');

        Swal.fire({
            title: "Retry Failed Dispatch Lead?",
            text: "This will attempt to reprocess the failed dispatch lead.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Retry",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('failed-operations.failed-dispatch.retry') }}", { id: leadId, _token: "{{ csrf_token() }}" })
                .done(function (response) {
                    Swal.fire("Success", response.success, "success");
                    if (dispatchTable) {
                        dispatchTable.ajax.reload();
                    }
                })
                .fail(function (xhr) {
                    Swal.fire("Error", xhr.responseJSON.error, "error");
                });
            }
        });
    });


    $(document).on('click', '.btn-download-log-file', function () {
        let activeTab = $('button[data-bs-toggle="tab"].active').data('tab-type');
        let logFileName = "";

        if (activeTab === "system-logs") {
            logFileName = $("#logFileSelector").val();
        } else if (activeTab === "system-failed-logs") {
            logFileName = $("#systemFailedLogFileSelector").val();
        } else if (activeTab === "export-logs") {
            logFileName = $("#exportLogFileSelector").val();
        }

        if (logFileName) {
            const downloadUrl = `{{ route('failed-operations.logs.download', ['type' => '__TYPE__', 'filename' => '__FILENAME__']) }}`
                .replace('__TYPE__', encodeURIComponent(activeTab))
                .replace('__FILENAME__', encodeURIComponent(logFileName));

            window.location.href = downloadUrl;
        } else {
            toastr.error("Please select a log file to download.", "Missing File", { timeOut: 2000 });
        }
    });

    $(document).on('click', '.btn-delete-log-file', function () {
        let activeTab = $('button[data-bs-toggle="tab"].active').data('tab-type');
        let logFileName = "";

        if (activeTab === "system-logs") {
            logFileName = $("#logFileSelector").val();
        } else if (activeTab === "system-failed-logs") {
            logFileName = $("#systemFailedLogFileSelector").val();
        } else if (activeTab === "export-logs") {
            logFileName = $("#exportLogFileSelector").val();
        }

        if (!logFileName) {
            toastr.error("Please select a log file to delete.", "Missing File", { timeOut: 2000 });
            return;
        }

        Swal.fire({
            title: "Are you sure?",
            text: "This log file will be permanently deleted. Make sure to download it first if needed!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteUrl = `{{ route('failed-operations.logs.delete', ['type' => '__TYPE__', 'filename' => '__FILENAME__']) }}`
                    .replace('__TYPE__', encodeURIComponent(activeTab))
                    .replace('__FILENAME__', encodeURIComponent(logFileName));

                // Redirect to delete route
                window.location.href = deleteUrl;
            }
        });
    });
});

</script>
@endsection