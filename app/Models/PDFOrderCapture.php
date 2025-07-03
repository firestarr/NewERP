<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sales\SalesOrder;
use App\Models\User;

class PdfOrderCapture extends Model
{
    use HasFactory;

    protected $table = 'pdf_order_captures';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'filename',
        'file_path',
        'file_size',
        'ai_raw_response',
        'extracted_data',
        'status',
        'processing_error',
        'created_so_id',
        'confidence_score',
        'processed_by',
        'processed_at',
        'user_id',
        'processing_options',
        'item_validation' // ADDED: Support for item validation data
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'ai_raw_response' => 'array',
        'processing_options' => 'array',
        'item_validation' => 'array', // ADDED: Cast item validation as array
        'confidence_score' => 'float',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // FIXED: Status constants - aligned with controller usage
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DATA_EXTRACTED = 'data_extracted';
    const STATUS_EXTRACTED = 'extracted';
    const STATUS_VALIDATING = 'validating';
    const STATUS_CREATING_ORDER = 'creating_order';
    const STATUS_SO_CREATED = 'so_created';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * ADDED: Accessor for human readable file size
     */
    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 1) . ' ' . $units[$unitIndex];
    }

    /**
     * ADDED: Accessor for extracted customer data
     */
    public function getExtractedCustomerAttribute()
    {
        return $this->extracted_data['customer'] ?? null;
    }

    /**
     * ADDED: Accessor for extracted items data
     */
    public function getExtractedItemsAttribute()
    {
        return $this->extracted_data['items'] ?? [];
    }

    /**
     * Get the sales order that was created from this capture
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'created_so_id', 'so_id');
    }

    /**
     * Get the user who processed this capture
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the user who uploaded this capture
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mark as completed with SO created
     */
    public function markAsCompleted($soId)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'created_so_id' => $soId,
            'processed_at' => now()
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($error)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processing_error' => $error
        ]);
    }

    /**
     * Check if processing was successful
     */
    public function isSuccessful()
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_SO_CREATED,
            self::STATUS_DATA_EXTRACTED
        ]);
    }

    /**
     * Check if capture failed
     */
    public function hasFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if capture is ready for SO creation
     */
    public function canCreateSalesOrder()
    {
        // Must be in data_extracted status
        if ($this->status !== self::STATUS_DATA_EXTRACTED) {
            return false;
        }

        // Must not already have a sales order created
        if ($this->created_so_id || $this->salesOrder) {
            return false;
        }

        // FIXED: Check item validation - no missing items allowed (EXACT MATCH REQUIRED)
        if (!$this->item_validation) {
            return false;
        }

        $missingItems = $this->item_validation['missing_items'] ?? [];
        return empty($missingItems);
    }

    /**
     * Get validation summary
     */
    public function getValidationSummary()
    {
        if (!$this->item_validation) {
            return [
                'total_items' => 0,
                'found_items' => 0,
                'missing_items' => 0,
                'can_create_so' => false
            ];
        }

        $totalItems = count($this->extracted_data['items'] ?? []);
        $foundItems = count($this->item_validation['existing_items'] ?? []);
        $missingItems = count($this->item_validation['missing_items'] ?? []);

        return [
            'total_items' => $totalItems,
            'found_items' => $foundItems,
            'missing_items' => $missingItems,
            'can_create_so' => $missingItems === 0
        ];
    }

    /**
     * ADDED: Static method to get statistics
     */
    public static function getStatistics($userId = null, $days = 30)
    {
        $query = self::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }
        
        $total = $query->count();
        $completed = $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_SO_CREATED])->count();
        $processing = $query->where('status', self::STATUS_PROCESSING)->count();
        $failed = $query->where('status', self::STATUS_FAILED)->count();
        $dataExtracted = $query->where('status', self::STATUS_DATA_EXTRACTED)->count();
        
        $successRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        
        // Average confidence score
        $avgConfidence = $query->whereNotNull('confidence_score')
                             ->avg('confidence_score');
        $avgConfidence = $avgConfidence ? round($avgConfidence, 1) : 0;
        
        return [
            'total' => $total,
            'completed' => $completed,
            'processing' => $processing,
            'failed' => $failed,
            'data_extracted' => $dataExtracted,
            'success_rate' => $successRate,
            'average_confidence' => $avgConfidence
        ];
    }

    /**
     * ADDED: Scope for filtering by status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * ADDED: Scope for recent captures
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * ADDED: Scope for captures by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * ADDED: Scope for successful captures
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_SO_CREATED,
            self::STATUS_DATA_EXTRACTED
        ]);
    }

    /**
     * ADDED: Scope for failed captures
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * ADDED: Check if using page-based processing
     */
    public function isPageBasedProcessing()
    {
        if (!$this->extracted_data) {
            return false;
        }
        
        $processingNotes = $this->extracted_data['processing_notes'] ?? '';
        $tableNotes = $this->extracted_data['table_structure_notes'] ?? '';
        
        return strpos($processingNotes, 'pages') !== false || 
               strpos($tableNotes, 'pages') !== false ||
               (isset($this->extracted_data['items']) && 
                collect($this->extracted_data['items'])->contains(function ($item) {
                    return isset($item['source_page']);
                }));
    }

    /**
     * ADDED: Get total pages processed
     */
    public function getTotalPages()
    {
        if (!$this->extracted_data || !isset($this->extracted_data['processing_notes'])) {
            return null;
        }
        
        $processingNotes = $this->extracted_data['processing_notes'];
        
        // Try to extract page count from processing notes
        if (preg_match('/(\d+)\s+pages?/', $processingNotes, $matches)) {
            return (int) $matches[1];
        }
        
        // Count unique source pages from items
        if (isset($this->extracted_data['items'])) {
            $pages = collect($this->extracted_data['items'])
                ->pluck('source_page')
                ->filter()
                ->unique()
                ->count();
            
            return $pages > 0 ? $pages : null;
        }
        
        return null;
    }

    /**
     * ADDED: Check if file is large (for processing method indication)
     */
    public function isLargeFile()
    {
        $threshold = 2 * 1024 * 1024; // 2MB
        return $this->file_size > $threshold;
    }

    /**
     * ADDED: Get processing method description
     */
    public function getProcessingMethod()
    {
        if ($this->isPageBasedProcessing()) {
            $pages = $this->getTotalPages();
            return $pages ? "Page-based ({$pages} pages)" : 'Page-based';
        }
        
        if ($this->isLargeFile()) {
            return 'Chunked Processing';
        }
        
        return 'Standard Processing';
    }

    /**
     * ADDED: Get error details if failed
     */
    public function getErrorDetails()
    {
        if ($this->status === self::STATUS_FAILED) {
            return [
                'error' => $this->processing_error,
                'can_retry' => true,
                'retry_suggestions' => [
                    'Check if PDF contains extractable text',
                    'Verify PDF is not password protected',
                    'Ensure pdftotext is installed on server'
                ]
            ];
        }
        
        return null;
    }

    /**
     * ADDED: Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Log when status changes
        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                \Log::info('PDF Capture status changed', [
                    'capture_id' => $model->id,
                    'old_status' => $model->getOriginal('status'),
                    'new_status' => $model->status,
                    'filename' => $model->filename
                ]);
            }
        });
    }
}