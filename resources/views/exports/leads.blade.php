
<div class="container">
    <h2>Exported Files</h2>
    <table class="table">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Status</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exports as $export)
                <tr>
                    <td>{{ $export->file_name }}</td>
                    <td>{{ $export->status }}</td>
                    <td>
                        @if($export->status === 'completed')
                            <a href="{{ route('exports.download', $export->file_name) }}" class="btn btn-success">Download</a>
                        @else
                            <span class="text-warning">Processing...</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>