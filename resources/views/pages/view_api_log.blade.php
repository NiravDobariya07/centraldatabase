@extends('layout.master')

@section('page-title', config('app.name') . ' - View API Log')
@section('custom-page-style')
<style>
    .log-content-table {
        border-left: none;
        border-right: none;
        width: 100%;
        border-collapse: collapse;
    }

    .log-content-table tr td {
        padding: 10px;
        border: 1px solid #dee2e6;
    }

    .log-content-table tr td:first-child {
        border-right: #838181 solid 1px;
        font-weight: 600;
        background-color: #f8f9fa;
        width: 200px;
    }

    .log-content-table tr.separator {
        height: 50px;
        background-color: #cccccc;
    }

    .log-content-table tr.separator td {
        border: none;
    }

    .log-div {
        overflow-x: auto;
        border: #838181 solid 1px;
        max-height: calc(100vh - 300px);
        overflow-y: auto;
    }

    .log-content {
        text-align: center;
        margin-bottom: 30px;
    }
</style>
@endsection

@section('page-content')

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Logs ({{ $date }})</h5>
                    <div>
                        <a href="{{ route('api-logs.list') }}" class="btn btn-sm btn-secondary">
                            <i class="bx bx-arrow-back"></i> Back to Logs
                        </a>
                        <a href="{{ route('api-logs.download', ['filename' => $filename]) }}" class="btn btn-sm btn-success">
                            <i class="bx bx-download"></i> Download
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(empty($logContent))
                        <h2 class="log-content pt-3">No Log Found</h2>
                    @else
                        <div class="log-div">
                            <table border="1" class="log-content-table">
                                @php
                                    $logEntries = explode("\n", trim($logContent));
                                    $entryIndex = 0;
                                @endphp

                                @foreach($logEntries as $entry)
                                    @if(empty(trim($entry)))
                                        @continue
                                    @endif

                                    @php
                                        $logData = json_decode($entry, true);
                                    @endphp

                                    @if(json_last_error() !== JSON_ERROR_NONE)
                                        @continue
                                    @endif

                                    @if($entryIndex > 0)
                                        <tr class="separator">
                                            <td colspan="2"></td>
                                        </tr>
                                    @endif

                                    @foreach($columns as $column)
                                        <tr>
                                            <td>{{ $column }}</td>
                                            <td>
                                                @if(isset($logData[$column]) && !empty($logData[$column]))
                                                    @php
                                                        $value = $logData[$column];
                                                        // Check if value is JSON string
                                                        if (is_string($value)) {
                                                            $decoded = json_decode($value, true);
                                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                                $value = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                                            }
                                                        } elseif (is_array($value) || is_object($value)) {
                                                            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                                        }
                                                    @endphp
                                                    <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">{{ $value }}</pre>
                                                @else
                                                    NA
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    @php $entryIndex++; @endphp
                                @endforeach
                            </table>
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

