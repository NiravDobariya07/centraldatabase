@extends('layout.master')

@section('page-title', config('app.name') . ' - Downloads')

@section('page-content')
<div class="container">
    <h4 class="text-primary">Download Your Exported Files</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>File</th>
                <th>Status</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exports as $export)
            <tr>
                <td>{{ basename($export->file_path) }}</td>
                <td>{{ ucfirst($export->status) }}</td>
                <td>
                    @if($export->status == 'completed')
                        <a href="{{ Storage::url($export->file_path) }}" class="btn btn-success btn-sm">Download</a>
                    @else
                        <span class="text-warning">Processing...</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
