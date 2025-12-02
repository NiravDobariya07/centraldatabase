<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeadExport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\{ AllContact, Export, ExportFile, ConsumerInsiteContact, TraContact, FlmApiLead, BlacklistListing, ExtLeadContact };
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

    public function exportData($query, $fields, $userId, $exportId, $maxId, $format = 'xlsx', $filePrefix = 'leads_export', $modelType = 'AllContact')
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

            $headers = array_map(fn($fieldKey) => getLeadKeyByValue($fieldKey, $modelType), $fields);
            $writer->addRow(WriterEntityFactory::createRowFromArray($headers, $headerStyle));

            $total = (clone $query)->where('id', '<=', $maxId)->count();
            Log::channel($this->exportLogChannel)->info("📦 {$logPrefix} Starting export. Total rows to process: {$total}");

            $processed = 0;
            (clone $query)
                ->where('id', '<=', $maxId)
                ->chunk($this->exportFetchChunkSize, function ($rows) use ($fields, $writer, $logPrefix, &$processed, $modelType) {
                    foreach ($rows as $item) {
                        $rowData = [];

                        foreach ($fields as $field) {
                            // Special handling for BlacklistListing source column
                            if ($modelType === 'BlacklistListing' && $field === 'source') {
                                $sourceType = trim($item->source_type ?? '');
                                $source = trim($item->source ?? '');

                                if (empty($sourceType) && empty($source)) {
                                    $value = 'N/A';
                                } elseif ($sourceType === $source) {
                                    $value = $sourceType ?: $source;
                                } elseif (empty($sourceType)) {
                                    $value = $source;
                                } elseif (empty($source)) {
                                    $value = $sourceType;
                                } else {
                                    $value = $sourceType . ' - ' . $source;
                                }
                            } else {
                                $value = data_get($item, $field);
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

    public function exportMultipleFilesAndZip($query, $fields, $userId, $exportId, $maxId, $format = 'xlsx', $filePrefix = 'leads_export', $modelType = 'AllContact')
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
                ->chunk($this->exportFetchChunkSize, function ($rows) use ($fields, &$fileParts, &$processed, $format, $logPrefix, $timestamp, $filePrefix, $userId, $exportId, &$partIndex, $modelType) {
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

                    $headers = array_map(fn($fieldKey) => getLeadKeyByValue($fieldKey, $modelType), $fields);
                    $headerStyle = (new StyleBuilder())->setFontSize(12)->build();
                    $writer->addRow(WriterEntityFactory::createRowFromArray($headers, $headerStyle));

                    foreach ($rows as $item) {
                        $rowData = [];
                        foreach ($fields as $field) {
                            // Special handling for BlacklistListing source column
                            if ($modelType === 'BlacklistListing' && $field === 'source') {
                                $sourceType = trim($item->source_type ?? '');
                                $source = trim($item->source ?? '');

                                if (empty($sourceType) && empty($source)) {
                                    $value = 'N/A';
                                } elseif ($sourceType === $source) {
                                    $value = $sourceType ?: $source;
                                } elseif (empty($sourceType)) {
                                    $value = $source;
                                } elseif (empty($source)) {
                                    $value = $sourceType;
                                } else {
                                    $value = $sourceType . ' - ' . $source;
                                }
                            } else {
                                $value = data_get($item, $field);
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
                $exportQuery = AllContact::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'email_domain' => 'email_domain',
                        'phone' => 'phone',
                        'aff_id' => 'aff_id',
                        'sub_id' => 'sub_id',
                        'journya' => 'journya',
                        'cake_leadid' => 'cake_leadid',
                        'optin_domain' => 'optin_domain',
                        'domain_abt' => 'domain_abt',
                        'trusted_form' => 'trusted_form',
                        'ip_address' => 'ip_address',
                        'esp' => 'esp',
                        'result' => 'result',
                        'offer_id' => 'offer_id',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('cake_leadid', 'LIKE', $searchTerm)
                            ->orWhere('email', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('email_domain', 'LIKE', $searchTerm)
                            ->orWhere('optin_domain', 'LIKE', $searchTerm)
                            ->orWhere('aff_id', 'LIKE', $searchTerm)
                            ->orWhere('sub_id', 'LIKE', $searchTerm)
                            ->orWhere('journya', 'LIKE', $searchTerm)
                            ->orWhere('trusted_form', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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
                                $filePrefix,
                                'TraContact'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'TraContact'
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

    public function processConsumerInsiteContactExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} Consumer Insite Contact export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = ConsumerInsiteContact::query()->where('deleted_at', 0);

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'age' => 'age',
                        'credit_score' => 'credit_score',
                        'location_name' => 'location_name',
                        'result' => 'result',
                        'resultid' => 'resultid',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('email', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('age', 'LIKE', $searchTerm)
                            ->orWhere('credit_score', 'LIKE', $searchTerm)
                            ->orWhere('location_name', 'LIKE', $searchTerm)
                            ->orWhere('result', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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
                $filePrefix = $exportScheduledData->file_prefix ?: 'consumer_insite_contacts_export';
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
                                $filePrefix,
                                'TraContact'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'TraContact'
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

    public function processTraContactExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} TRA Contact export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = TraContact::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'email_domain' => 'email_domain',
                        'phone' => 'phone',
                        'state' => 'state',
                        'zip_code' => 'zip_code',
                        'cake_id' => 'cake_id',
                        'aff_id' => 'aff_id',
                        'sub_id' => 'sub_id',
                        'offer_id' => 'offer_id',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('email', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('cake_id', 'LIKE', $searchTerm)
                            ->orWhere('email_domain', 'LIKE', $searchTerm)
                            ->orWhere('state', 'LIKE', $searchTerm)
                            ->orWhere('zip_code', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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
                $filePrefix = $exportScheduledData->file_prefix ?: 'tra_contacts_export';
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
                                $filePrefix,
                                'TraContact'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'TraContact'
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

    public function processSiteTokenExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} Site Token export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = \App\Models\Offer::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'offer_name' => 'offer_name',
                        'domain_abt' => 'domain_abt',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('offer_name', 'LIKE', $searchTerm)
                            ->orWhere('domain_abt', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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
                $filePrefix = $exportScheduledData->file_prefix ?: 'sites_tokens_export';
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
                                $filePrefix,
                                'Offer'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'Offer'
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

    public function processWhiteCollarLeadExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} WhiteCollar Lead export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = FlmApiLead::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'email_address' => 'email_address',
                        'lead_timestamp' => 'lead_timestamp',
                        'payout_paid' => 'payout_paid',
                        'result' => 'result',
                        'lead_id' => 'lead_id',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('lead_id', 'LIKE', $searchTerm)
                            ->orWhere('email_address', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('lead_timestamp', 'LIKE', $searchTerm)
                            ->orWhere('payout_paid', 'LIKE', $searchTerm)
                            ->orWhere('result', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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
                $filePrefix = $exportScheduledData->file_prefix ?: 'whitecollar_leads_export';
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
                                $filePrefix,
                                'FlmApiLead'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'FlmApiLead'
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

    public function processBlacklistExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} Blacklist export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = BlacklistListing::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'email' => 'email',
                        'response' => 'response',
                        'source' => 'source',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('email', 'LIKE', $searchTerm)
                            ->orWhere('response', 'LIKE', $searchTerm)
                            ->orWhere('source', 'LIKE', $searchTerm)
                            ->orWhere('source_type', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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
                $filePrefix = $exportScheduledData->file_prefix ?: 'blacklist_export';
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
                                $filePrefix,
                                'BlacklistListing'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'BlacklistListing'
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

    public function processExtLeadExport($exportId) {
        Log::channel($this->exportLogChannel)->info('🧠 PHP memory limit: ' . ini_get('memory_limit'));
        Log::channel($this->exportLogChannel)->info('⏱️ PHP max execution time: ' . ini_get('max_execution_time'));

        DB::beginTransaction();
        $logPrefix = "Export Id ({$exportId}) :";
        try {
            Log::channel($this->exportLogChannel)->info("🚀 {$logPrefix} Ext Lead export process started", ['export_id' => $exportId]);

            $exportScheduledData = Export::find($exportId);
            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['last_run_at' => now()]);
                $exportQuery = ExtLeadContact::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'phone' => 'phone',
                        'alt_phone' => 'alt_phone',
                        'address' => 'address',
                        'city' => 'city',
                        'state' => 'state',
                        'postal' => 'postal',
                        'country' => 'country',
                        'ip' => 'ip',
                        'source' => 'source',
                        'affid' => 'affid',
                        'subid' => 'subid',
                        'lead_id' => 'lead_id',
                        'jornaya_id' => 'jornaya_id',
                        'trusted_form_id' => 'trusted_form_id',
                        'result' => 'result',
                        'offer_url' => 'offer_url',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('lead_id', 'LIKE', $searchTerm)
                            ->orWhere('email', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('source', 'LIKE', $searchTerm)
                            ->orWhere('affid', 'LIKE', $searchTerm)
                            ->orWhere('subid', 'LIKE', $searchTerm)
                            ->orWhere('jornaya_id', 'LIKE', $searchTerm)
                            ->orWhere('trusted_form_id', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates (using created_date)
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_date', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_date', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_date', '<=', $endDate);
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
                $filePrefix = $exportScheduledData->file_prefix ?: 'ext_lead_export';
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
                                $filePrefix,
                                'ExtLeadContact'
                            );
                        } else {
                            $generatedExportFileData = $this->exportData(
                                $exportQuery,
                                $fields,
                                $exportScheduledData->user_id,
                                $exportId,
                                $maxId,
                                $exportFilformat,
                                $filePrefix,
                                'ExtLeadContact'
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
                $exportQuery = AllContact::query();

                // Column-specific filter
                if (!empty($exportScheduledData->filters['filter_column']) && !empty($exportScheduledData->filters['search_value'])) {
                    $column = $exportScheduledData->filters['filter_column'];
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'email_domain' => 'email_domain',
                        'phone' => 'phone',
                        'aff_id' => 'aff_id',
                        'sub_id' => 'sub_id',
                        'journya' => 'journya',
                        'cake_leadid' => 'cake_leadid',
                        'optin_domain' => 'optin_domain',
                        'domain_abt' => 'domain_abt',
                        'trusted_form' => 'trusted_form',
                        'ip_address' => 'ip_address',
                        'esp' => 'esp',
                        'result' => 'result',
                        'offer_id' => 'offer_id',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $exportQuery->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($exportScheduledData->filters['search_value'])) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $exportScheduledData->filters['search_value'] . '%';
                    $exportQuery->where(function ($query) use ($searchTerm) {
                        $query->where('cake_leadid', 'LIKE', $searchTerm)
                            ->orWhere('email', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('email_domain', 'LIKE', $searchTerm)
                            ->orWhere('optin_domain', 'LIKE', $searchTerm)
                            ->orWhere('aff_id', 'LIKE', $searchTerm)
                            ->orWhere('sub_id', 'LIKE', $searchTerm)
                            ->orWhere('journya', 'LIKE', $searchTerm)
                            ->orWhere('trusted_form', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($exportScheduledData->filters['date_range']['from']) && !empty($exportScheduledData->filters['date_range']['to'])) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        throw new \Exception('End Date cannot be before Start Date');
                    }

                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['from'])) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($exportScheduledData->filters['date_range']['from'])->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $exportQuery->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($exportScheduledData->filters['date_range']['to'])) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($exportScheduledData->filters['date_range']['to'])->endOfDay();
                    $exportQuery->where('created_at', '<=', $endDate);
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

