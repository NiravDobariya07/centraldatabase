<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\ShouldQueue;

class LeadExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, ShouldAutoSize
{
    use Exportable;

    protected $model;
    protected $fields;
    protected $format;

    public function __construct($model, $fields, $format = 'xlsx')
    {
        $this->model = $model;
        $this->fields = $fields;
        $this->format = $format;
    }

    public function query()
    {
        // Replace 'list_id' with 'campaign_list_id' in the selection
        $fields = array_map(fn($field) => $field === 'list_id' ? 'campaign_list_id' : $field, $this->fields);

        $query = $this->model->select($fields);

        // Load relation only when 'list_id' is required
        if (in_array('list_id', $this->fields)) {
            $query->with('campaignList');
        }

        return $query;
    }

    public function headings(): array
    {
        if (request()->input('format') === 'csv') {
            return [chr(239) . chr(187) . chr(191)] + $this->fields; // Add BOM for CSV
        }

        return $this->fields; // Normal headings for XLSX
    }

    public function map($row): array
    {
        return collect($this->fields)
        ->map(function ($field) use ($row) {
            if ($field === 'list_id') {
                return $row->campaignList->list_id ?? null; // Fetch from relation
            }
            return $row->$field ?? null;
        })
        ->toArray();
    }

    public function chunkSize(): int
    {
        return 1000; // Adjust chunk size based on server memory
    }
}
