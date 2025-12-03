@extends('layout.master')

@section('page-title', config('app.name') . ' - API Logs')
@section('page-content')

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">List Of Logs File</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th>Log File Name</th>
                                    <th>Created At</th>
                                    <th>File Size</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(empty($logFiles))
                                    <tr>
                                        <td colspan="5" class="text-center">No log files found.</td>
                                    </tr>
                                @else
                                    @foreach($logFiles as $index => $logFile)
                                        @php
                                            $datePart = explode('_', $logFile['filename'])[1] ?? '';
                                            $dateOnly = substr($datePart, 0, -4);
                                            $dateWithSlashes = str_replace('.', '/', $dateOnly);
                                            $fileSize = $logFile['size'] > 1024 ? number_format($logFile['size'] / 1024, 2) . ' KB' : $logFile['size'] . ' bytes';
                                            $srNo = (($currentPage - 1) * 10) + $index + 1;
                                        @endphp
                                        <tr>
                                            <td>{{ $srNo }}</td>
                                            <td>{{ $logFile['filename'] }}</td>
                                            <td>{{ $dateWithSlashes }}</td>
                                            <td>{{ $fileSize }}</td>
                                            <td>
                                                <a href="{{ route('api-logs.view', ['filename' => $logFile['filename']]) }}"
                                                   class="btn btn-sm btn-primary" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('api-logs.download', ['filename' => $logFile['filename']]) }}"
                                                   class="btn btn-sm btn-success" title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                                <a href="{{ route('api-logs.delete', ['filename' => $logFile['filename']]) }}"
                                                   class="btn btn-sm btn-danger"
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this log file?')">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(isset($totalPages) && $totalPages > 1)
                        <!-- Pagination -->
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-end">
                                <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="?page={{ max(1, $currentPage - 1) }}" aria-label="Previous">
                                        <span aria-hidden="true">&laquo; Previous</span>
                                    </a>
                                </li>

                                @for($i = 1; $i <= $totalPages; $i++)
                                    @if($i == 1 || $i == $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2))
                                        <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                            <a class="page-link" href="?page={{ $i }}">{{ $i }}</a>
                                        </li>
                                    @elseif($i == $currentPage - 3 || $i == $currentPage + 3)
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                @endfor

                                <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                    <a class="page-link" href="?page={{ min($totalPages, $currentPage + 1) }}" aria-label="Next">
                                        <span aria-hidden="true">Next &raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="text-muted text-center mt-2">
                            Showing {{ (($currentPage - 1) * 10) + 1 }} to {{ min($currentPage * 10, $totalLogs) }} of {{ $totalLogs }} log files
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom-page-scripts')
<script>
    $(document).ready(function() {
        $('.menu-item').removeClass('active');
        $('.menu-item-api-logs').addClass('active');
    });
</script>
@endsection

