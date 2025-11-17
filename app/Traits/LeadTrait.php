<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeadExport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\{ Lead, Export, ExportFile };
use App\Mail\ExportFileGeneratedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Constants\AppConstants;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Illuminate\Support\Facades\File;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;

trait LeadTrait
{
    protected string $exportLogChannel = 'export_daily';
    protected int $exportFetchChunkSize = 100000;

    public function exportData($query, $fields, $userId, $exportId, $maxId, $format = 'xlsx', $filePrefix = 'leads_export')
    {
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} Export process started", [
                'user_id' => $userId,
                'export_id' => $exportId,
                'format' => $format,
                'file_prefix' => $filePrefix
            ]);

            if (empty($query) || empty($fields) || empty($exportId)) {
                Log::channel($this->exportLogChannel)->warning("⚠️ {$logPrefix} Export Failed: Missing required parameters", [
                    'query' => $query,
                    'fields' => $fields,
                    'exportId' => $exportId,
                    'user_id' => $userId,
                ]);
                return null;
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $filePrefix = !empty(trim($filePrefix)) ? trim($filePrefix) : 'leads_export';
            $fileName = "{$filePrefix}_{$timestamp}.{$format}";
            $filePath = "exports/{$userId}/{$exportId}/{$fileName}";

            $writer = match (strtolower($format)) {
                'csv' => WriterEntityFactory::createCSVWriter(),
                'xlsx' => WriterEntityFactory::createXLSXWriter(),
                default => throw new \Exception("❌ Unsupported format: {$format}"),
            };

            Storage::disk('local')->makeDirectory(dirname($filePath), 0755, true);
            $fullPath = Storage::disk('local')->path($filePath);

            $defaultStyle = (new StyleBuilder())
                ->setFontSize(11)
                ->setCellAlignment(CellAlignment::LEFT)
                ->build();

            $headerStyle = (new StyleBuilder())
                ->setFontSize(12)
                ->build();

            $writer->setDefaultRowStyle($defaultStyle)->openToFile($fullPath);

            $headers = array_map(fn($fieldKey) => getLeadKeyByValue($fieldKey), $fields);
            $writer->addRow(WriterEntityFactory::createRowFromArray($headers, $headerStyle));

            $total = (clone $query)->where('id', '<=', $maxId)->count();
            Log::channel($this->exportLogChannel)->info("📦 {$logPrefix} Starting export. Total rows to process: {$total}");

            $processed = 0;
            (clone $query)
                ->where('id', '<=', $maxId)
                ->chunk($this->exportFetchChunkSize, function ($rows) use ($fields, $writer, $logPrefix, &$processed) {
                    foreach ($rows as $item) {
                        $rowData = [];

                        foreach ($fields as $field) {
                            $value = data_get($item, $field);

                            if ($field === 'list_id') {
                                $value = $item->campaign_list_data->list_id ?? '';
                            }

                            if ($value instanceof \Carbon\Carbon) {
                                $value = $value->toDateTimeString();
                            }

                            $rowData[] = $value;
                        }

                        $writer->addRow(WriterEntityFactory::createRowFromArray($rowData));
                        $processed++;
                    }

                    Log::channel($this->exportLogChannel)->info("🔄 {$logPrefix} Chunk complete — Processed so far: {$processed} rows");
                });

            $writer->close();

            Log::channel($this->exportLogChannel)->info("✅ {$logPrefix} Export complete. Total rows processed: {$processed}");

            $fileSize = Storage::disk('local')->size($filePath);

            Log::channel($this->exportLogChannel)->info("📁 {$logPrefix} Export file created", [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'user_id' => $userId,
                'export_id' => $exportId,
            ]);

            return [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize
            ];
        } catch (\Exception $e) {
            reportException(
                $e,
                "❌ {$logPrefix} Export Failed for User ID: " . ($userId ?? 'N/A') . " and Export ID: " . ($exportId ?? 'N/A'),
                true,
                $this->exportLogChannel
            );
            throw $e;
        }
    }

    public function exportMultipleFilesAndZip($query, $fields, $userId, $exportId, $maxId, $format = 'xlsx', $filePrefix = 'leads_export')
    {
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("{$logPrefix} 🚀 Export process initiated.", [
                'user_id' => $userId,
                'export_id' => $exportId,
                'format' => $format,
                'file_prefix' => $filePrefix,
                'max_id' => $maxId
            ]);

            if (empty($query) || empty($fields) || empty($exportId)) {
                Log::channel($this->exportLogChannel)->warning("{$logPrefix} ❌ Export failed: Missing required parameters.", [
                    'query' => $query,
                    'fields' => $fields,
                    'exportId' => $exportId,
                    'user_id' => $userId,
                ]);
                return null;
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $filePrefix = !empty(trim($filePrefix)) ? trim($filePrefix) : 'leads_export';

            $fileParts = [];
            $processed = 0;
            $partIndex = 0;

            Log::channel($this->exportLogChannel)->info("{$logPrefix} 📦 Starting chunked export process.");

            (clone $query)
                ->where('id', '<=', $maxId)
                ->chunk($this->exportFetchChunkSize, function ($rows) use ($fields, &$fileParts, &$processed, $format, $logPrefix, $timestamp, $filePrefix, $userId, $exportId, &$partIndex) {
                    $partIndex++;

                    // 🧠 Log memory usage after chunk is fetched
                    Log::channel($this->exportLogChannel)->info("{$logPrefix} 📦 Chunk {$partIndex} loaded", [
                        'memory_usage_MB' => round(memory_get_usage(true) / 1048576, 2),
                        'peak_memory_usage_MB' => round(memory_get_peak_usage(true) / 1048576, 2),
                        'chunk_rows_count' => count($rows),
                    ]);

                    $fileName = "{$filePrefix}__Part{$partIndex}_{$timestamp}.{$format}";
                    $filePath = "exports/{$userId}/{$exportId}/temp-files/{$fileName}";

                    $writer = match (strtolower($format)) {
                        'csv' => WriterEntityFactory::createCSVWriter(),
                        'xlsx' => WriterEntityFactory::createXLSXWriter(),
                        default => throw new \Exception("Unsupported format: {$format}"),
                    };

                    Storage::disk('local')->makeDirectory(dirname($filePath), 0755, true);
                    $fullPath = Storage::disk('local')->path($filePath);

                    Log::channel($this->exportLogChannel)->info("{$logPrefix} 📝 Creating export file: {$fileName}");

                    $writer->openToFile($fullPath);

                    $headers = array_map(fn($fieldKey) => getLeadKeyByValue($fieldKey), $fields);
                    $headerStyle = (new StyleBuilder())->setFontSize(12)->build();
                    $writer->addRow(WriterEntityFactory::createRowFromArray($headers, $headerStyle));

                    foreach ($rows as $item) {
                        $rowData = [];
                        foreach ($fields as $field) {
                            $value = data_get($item, $field);
                            if ($field === 'list_id') {
                                $value = $item->campaign_list_data->list_id ?? '';
                            }
                            if ($value instanceof \Carbon\Carbon) {
                                $value = $value->toDateTimeString();
                            }
                            $rowData[] = $value;
                        }
                        $writer->addRow(WriterEntityFactory::createRowFromArray($rowData));
                        $processed++;
                    }

                    $writer->close();
                    $fileParts[] = $fullPath;

                    Log::channel($this->exportLogChannel)->info("{$logPrefix} ✅ Part {$partIndex} completed.", [
                        'file' => $fileName,
                        'rows_processed' => $processed,
                    ]);
                });

            Log::channel($this->exportLogChannel)->info("{$logPrefix} 🗜️ Creating ZIP file from parts...", [
                'total_parts' => count($fileParts),
                'parts' => array_map('basename', $fileParts)
            ]);

            $dateTime = date('Y-m-d');
            $zipFileName = "{$filePrefix}_{$format}_export_{$dateTime}.zip";
            $zipFilePath = "exports/{$userId}/{$exportId}/{$zipFileName}";
            $zipFullPath = Storage::disk('local')->path($zipFilePath);

            $zip = new \ZipArchive;

            if ($zip->open($zipFullPath, \ZipArchive::CREATE) === TRUE) {
                foreach ($fileParts as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                Log::channel($this->exportLogChannel)->info("{$logPrefix} 📦 ZIP file successfully created.", [
                    'zip_file_name' => $zipFileName,
                    'zip_file_path' => $zipFilePath
                ]);

                foreach ($fileParts as $file) {
                    @unlink($file);
                }

                Log::channel($this->exportLogChannel)->info("{$logPrefix} 🧹 Temporary export part files deleted.");

                return [
                    'file_path' => $zipFilePath,
                    'file_name' => $zipFileName,
                    'file_size' => Storage::disk('local')->size($zipFilePath)
                ];
            } else {
                Log::channel($this->exportLogChannel)->error("{$logPrefix} ❌ Failed to create ZIP file.");
                throw new \Exception("Failed to create zip file.");
            }

        } catch (\Exception $e) {
            reportException(
                $e,
                "{$logPrefix} ❌ Export failed for User ID: " . ($userId ?? 'N/A') . " and Export ID: " . ($exportId ?? 'N/A'),
                true,
                $this->exportLogChannel
            );
            throw $e;
        }
    }

    public function processLeadExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} Lead export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = Lead::query();

                // Search filter
                $exportQuery->when(!empty($exportScheduledData->filters['search_value']), function ($query) use ($exportScheduledData) {
                    $query->whereRaw(
                        "to_tsvector('english', search_vector) @@ websearch_to_tsquery(?)",
                        [$exportScheduledData->filters['search_value']]
                    );
                });

                // Source site filter
                $exportQuery->when(!empty($exportScheduledData->filters['source_site_id']) && is_array($exportScheduledData->filters['source_site_id']), function ($query) use ($exportScheduledData) {
                    $query->whereIn('source_site_id', $exportScheduledData->filters['source_site_id']);
                });

                // Campaign list filter
                $exportQuery->when(!empty($exportScheduledData->filters['campaign_list_id']) && is_array($exportScheduledData->filters['campaign_list_id']), function ($query) use ($exportScheduledData) {
                    $query->whereIn('campaign_list_id', $exportScheduledData->filters['campaign_list_id']);
                });

                // Date subscribed filter
                $exportQuery->when(
                    !empty($exportScheduledData->filters['date_subscribed']['from']) && !empty($exportScheduledData->filters['date_subscribed']['to']),
                    function ($query) use ($exportScheduledData) {
                        $query->whereBetween('date_subscribed', [
                            Carbon::parse($exportScheduledData->filters['date_subscribed']['from'])->startOfDay(),
                            Carbon::parse($exportScheduledData->filters['date_subscribed']['to'])->endOfDay()
                        ]);
                    }
                );

                // Import date filter
                $exportQuery->when(
                    !empty($exportScheduledData->filters['import_date']['from']) && !empty($exportScheduledData->filters['import_date']['to']),
                    function ($query) use ($exportScheduledData) {
                        $query->whereBetween('import_date', [
                            Carbon::parse($exportScheduledData->filters['import_date']['from'])->startOfDay(),
                            Carbon::parse($exportScheduledData->filters['import_date']['to'])->endOfDay()
                        ]);
                    }
                );

                // Tax debt amount filter
                if (
                    !empty($exportScheduledData->filters['tax_debt_amount']) &&
                    isset($exportScheduledData->filters['tax_debt_amount']['operator'], $exportScheduledData->filters['tax_debt_amount']['value']) &&
                    $exportScheduledData->filters['tax_debt_amount']['value'] !== null &&
                    $exportScheduledData->filters['tax_debt_amount']['value'] !== ''
                ) {
                    $exportQuery->where(
                        'tax_debt_amount',
                        $exportScheduledData->filters['tax_debt_amount']['operator'],
                        $exportScheduledData->filters['tax_debt_amount']['value']
                    );
                }

                // CC debt amount filter
                if (
                    !empty($exportScheduledData->filters['cc_debt_amount']) &&
                    isset($exportScheduledData->filters['cc_debt_amount']['operator'], $exportScheduledData->filters['cc_debt_amount']['value']) &&
                    $exportScheduledData->filters['cc_debt_amount']['value'] !== null &&
                    $exportScheduledData->filters['cc_debt_amount']['value'] !== ''
                ) {
                    $exportQuery->where(
                        'cc_debt_amount',
                        $exportScheduledData->filters['cc_debt_amount']['operator'],
                        $exportScheduledData->filters['cc_debt_amount']['value']
                    );
                }

                // Apply sorting if 'sort_by' exists in additional_data
                $exportQuery->when(!empty($exportScheduledData->additional_data['sort_by']['field']) && !empty($exportScheduledData->additional_data['sort_by']['sorting_order']), 
                    function ($query) use ($exportScheduledData) {
                        $query->orderBy(
                            $exportScheduledData->additional_data['sort_by']['field'], 
                            $exportScheduledData->additional_data['sort_by']['sorting_order']
                        );
                    }
                );
                
                $fields = $exportScheduledData->columns;
                $filePrefix = $exportScheduledData->file_prefix;
                // Snapshot max ID to prevent export of records added during the run
                $maxId = (clone $exportQuery)->max('id') ?? 0;

                if (!empty($exportScheduledData->export_formats) && is_array($exportScheduledData->export_formats)) {
                    foreach ($exportScheduledData->export_formats as $exportFilformat) {
                        Log::channel($this->exportLogChannel)->info("📄 {$logPrefix} Generating export file", ['format' => $exportFilformat]);

                        if (!empty($exportScheduledData->additional_data['export_in_batches'])) {
                            $generatedExportFileData = $this->exportMultipleFilesAndZip(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix
                            );
                        }
        
                        if (!empty($generatedExportFileData['file_path']) && !empty($generatedExportFileData['file_name'])) {
                            $createFilePostData = [
                                'export_id' => $exportScheduledData->id,
                                'user_id' => $exportScheduledData->user_id,
                                'file_name' => $generatedExportFileData['file_name'],
                                'file_path' => $generatedExportFileData['file_path'],
                                'file_format' => $exportFilformat,
                                'file_size' => !empty($generatedExportFileData['file_size']) ? $generatedExportFileData['file_size'] : 0,
                                'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ];
                            $exportFileData = ExportFile::create($createFilePostData);

                            if (!empty($exportFileData)) {
                                Log::channel($this->exportLogChannel)->info(sprintf(
                                    "✅ {$logPrefix} Export file generated successfully (ID: %d) 📁 Path: %s",
                                    $exportFileData->id ?? 0,
                                    $exportFileData->file_path ?? 'N/A'
                                ));

                                // Queue email AFTER transaction commit
                                DB::afterCommit(function () use ($exportScheduledData, $exportFileData, $logPrefix) {
                                    // Send Email through Queue:
                                    Mail::to($exportScheduledData->user->email)
                                        ->queue((new ExportFileGeneratedMail($exportFileData))
                                        ->onQueue('high-priority'));

                                    Log::channel($this->exportLogChannel)->info(sprintf(
                                        "📧 {$logPrefix} Email notification scheduled for Export File ID: %d → %s",
                                        $exportFileData->id ?? 0,
                                        $exportScheduledData->user->email ?? 'N/A'
                                    ));
                                });
                            } else {
                                Log::channel($this->exportLogChannel)->warning("❗{$logPrefix} Failed to create export file record.", $createFilePostData);
                                throw new \Exception("Failed to create export file record.");
                            } 
                        } else {
                            Log::channel($this->exportLogChannel)->warning("❗ {$logPrefix} Failed to generate export file.", ['generated_export_data' => $generatedExportFileData]);
                            throw new \Exception("Failed to generate export file.");
                        }
                    }
                } else {
                    Log::channel($this->exportLogChannel)->warning("❗ {$logPrefix} Export format not specified for export process.", ['export_scheduled_data' => $exportScheduledData]);
                    throw new \Exception("No export format specified.");  
                }

                $nextStatus = AppConstants::EXPORT_RUNING_STATUS['PENDING'];
                $nextRunAt = $exportScheduledData->calculateNextRun();
                if (!empty($exportScheduledData->frequency) && !empty($exportScheduledData->status)) {
                    if ($exportScheduledData->status == AppConstants::EXPORT_STATUSES['ACTIVE']) {
                        if ($exportScheduledData->frequency == 'one_time') {
                            $nextStatus = AppConstants::EXPORT_RUNING_STATUS['SUCCESS'];
                        }
                    } else if ($exportScheduledData->status == AppConstants::EXPORT_STATUSES['PAUSED']) {
                        $nextStatus = AppConstants::EXPORT_RUNING_STATUS['PAUSED'];
                        $nextRunAt = null;

                    } else if ($exportScheduledData->status == AppConstants::EXPORT_STATUSES['STOPPED']) {
                        $nextStatus = AppConstants::EXPORT_RUNING_STATUS['STOPPED'];
                        $nextRunAt = null;
                    }
                }

                $exportScheduledData->update([
                    'next_run_at' => $nextRunAt,
                    'runing_status' => $nextStatus
                ]);

                Log::channel($this->exportLogChannel)->info("🔄 {$logPrefix} Export schedule updated.", [
                    'export_id'    => $exportScheduledData->id ?? 'N/A',
                    'status'       => $exportScheduledData->status ?? 'N/A',
                    'frequency'    => $exportScheduledData->frequency ?? 'N/A',
                    'next_run_at'  => $nextRunAt ?? 'N/A',
                    'next_status'  => $nextStatus ?? 'N/A',
                ]);
            } else {
                Log::channel($this->exportLogChannel)->warning("❌ {$logPrefix} Export not found for Export ID: {$exportId}", [
                    'export_id' => $exportId ?? 'N/A',
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($exportId)) {
                $exportScheduledData = Export::find($exportId);
                $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['FAILED']]);

                Log::channel($this->exportLogChannel)->error("❌ {$logPrefix} Export marked as FAILED.", [
                    'export_id' => $exportId,
                    'error'     => $e->getMessage()
                ]);
            }
            reportException($e, "Error Export", true, $this->exportLogChannel);
            throw $e; // Correct syntax for rethrowing the exception
        }        
    }

    public function getExportableRecordCount($exportId) {
        $logPrefix = "Export Id ({$exportId}) :";
        $totalExportableRecordsCount = 0;
        try {
            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportQuery = Lead::query();

                // Search filter
                $exportQuery->when(!empty($exportScheduledData->filters['search_value']), function ($query) use ($exportScheduledData) {
                    $query->whereRaw(
                        "to_tsvector('english', search_vector) @@ websearch_to_tsquery(?)",
                        [$exportScheduledData->filters['search_value']]
                    );
                });

                // Source site filter
                $exportQuery->when(!empty($exportScheduledData->filters['source_site_id']) && is_array($exportScheduledData->filters['source_site_id']), function ($query) use ($exportScheduledData) {
                    $query->whereIn('source_site_id', $exportScheduledData->filters['source_site_id']);
                });

                // Campaign list filter
                $exportQuery->when(!empty($exportScheduledData->filters['campaign_list_id']) && is_array($exportScheduledData->filters['campaign_list_id']), function ($query) use ($exportScheduledData) {
                    $query->whereIn('campaign_list_id', $exportScheduledData->filters['campaign_list_id']);
                });

                // Date subscribed filter
                $exportQuery->when(
                    !empty($exportScheduledData->filters['date_subscribed']['from']) && !empty($exportScheduledData->filters['date_subscribed']['to']),
                    function ($query) use ($exportScheduledData) {
                        $query->whereBetween('date_subscribed', [
                            Carbon::parse($exportScheduledData->filters['date_subscribed']['from'])->startOfDay(),
                            Carbon::parse($exportScheduledData->filters['date_subscribed']['to'])->endOfDay()
                        ]);
                    }
                );

                // Import date filter
                $exportQuery->when(
                    !empty($exportScheduledData->filters['import_date']['from']) && !empty($exportScheduledData->filters['import_date']['to']),
                    function ($query) use ($exportScheduledData) {
                        $query->whereBetween('import_date', [
                            Carbon::parse($exportScheduledData->filters['import_date']['from'])->startOfDay(),
                            Carbon::parse($exportScheduledData->filters['import_date']['to'])->endOfDay()
                        ]);
                    }
                );

                // Tax debt amount filter
                if (
                    !empty($exportScheduledData->filters['tax_debt_amount']) &&
                    isset($exportScheduledData->filters['tax_debt_amount']['operator'], $exportScheduledData->filters['tax_debt_amount']['value']) &&
                    $exportScheduledData->filters['tax_debt_amount']['value'] !== null &&
                    $exportScheduledData->filters['tax_debt_amount']['value'] !== ''
                ) {
                    $exportQuery->where(
                        'tax_debt_amount',
                        $exportScheduledData->filters['tax_debt_amount']['operator'],
                        $exportScheduledData->filters['tax_debt_amount']['value']
                    );
                }

                // CC debt amount filter
                if (
                    !empty($exportScheduledData->filters['cc_debt_amount']) &&
                    isset($exportScheduledData->filters['cc_debt_amount']['operator'], $exportScheduledData->filters['cc_debt_amount']['value']) &&
                    $exportScheduledData->filters['cc_debt_amount']['value'] !== null &&
                    $exportScheduledData->filters['cc_debt_amount']['value'] !== ''
                ) {
                    $exportQuery->where(
                        'cc_debt_amount',
                        $exportScheduledData->filters['cc_debt_amount']['operator'],
                        $exportScheduledData->filters['cc_debt_amount']['value']
                    );
                }

                // Apply sorting if 'sort_by' exists in additional_data
                $exportQuery->when(!empty($exportScheduledData->additional_data['sort_by']['field']) && !empty($exportScheduledData->additional_data['sort_by']['sorting_order']), 
                    function ($query) use ($exportScheduledData) {
                        $query->orderBy(
                            $exportScheduledData->additional_data['sort_by']['field'],
                            $exportScheduledData->additional_data['sort_by']['sorting_order']
                        );
                    }
                );

                // Snapshot max ID to prevent export of records added during the run
                $maxId = (clone $exportQuery)->max('id') ?? 0;
                $totalExportableRecordsCount = (clone $exportQuery)->where('id', '<=', $maxId)->count();

            } else {
                Log::channel($this->exportLogChannel)->warning("❗ {$logPrefix} Export not found (getExportableRecordCount) for Export ID: {$exportId}.", [
                    'export_id' => $exportId,
                ]);
            }
        } catch (\Exception $e) {
            reportException($e, "Error getExportableRecordCount", true, $this->exportLogChannel);
        }

        return $totalExportableRecordsCount;
    }
}