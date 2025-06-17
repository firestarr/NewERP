<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustments';
    protected $primaryKey = 'adjustment_id';
    protected $fillable = [
        'adjustment_date', 
        'adjustment_reason', 
        'status', 
        'reference_document'
    ];

    protected $dates = [
        'adjustment_date',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get the adjustment lines for this adjustment
     */
    public function adjustmentLines()
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id', 'adjustment_id');
    }

    /**
     * Get the total variance quantity
     */
    public function getTotalVarianceAttribute()
    {
        return $this->adjustmentLines->sum('variance');
    }

    /**
     * Get the total absolute variance (for display purposes)
     */
    public function getTotalAbsVarianceAttribute()
    {
        return $this->adjustmentLines->sum(function($line) {
            return abs($line->variance);
        });
    }

    /**
     * Get status badge class for frontend
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return 'badge-secondary';
            case self::STATUS_PENDING:
                return 'badge-warning';
            case self::STATUS_APPROVED:
                return 'badge-info';
            case self::STATUS_COMPLETED:
                return 'badge-success';
            case self::STATUS_REJECTED:
                return 'badge-danger';
            default:
                return 'badge-light';
        }
    }

    /**
     * Check if adjustment can be edited
     */
    public function canBeEdited()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if adjustment can be submitted
     */
    public function canBeSubmitted()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if adjustment can be approved
     */
    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if adjustment can be processed
     */
    public function canBeProcessed()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if adjustment can be deleted
     */
    public function canBeDeleted()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Process the stock adjustment by updating ItemStock records
     * This replaces the old process() method that directly updated Item.current_stock
     */
    public function process()
    {
        if ($this->status != self::STATUS_APPROVED) {
            return false;
        }

        foreach ($this->adjustmentLines as $line) {
            if ($line->variance != 0) {
                // Update ItemStock for specific warehouse
                $itemStock = ItemStock::firstOrNew([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $line->warehouse_id
                ]);
                
                if (!$itemStock->exists) {
                    $itemStock->quantity = 0;
                    $itemStock->reserved_quantity = 0;
                }
                
                // Set the new quantity directly from adjusted_quantity
                $itemStock->quantity = $line->adjusted_quantity;
                $itemStock->save();

                // Create stock transaction for audit trail
                $moveType = $line->variance > 0 ? 
                    StockTransaction::MOVE_TYPE_IN : 
                    StockTransaction::MOVE_TYPE_OUT;
                
                StockTransaction::create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $line->warehouse_id,
                    'dest_warehouse_id' => null,
                    'transaction_type' => StockTransaction::TYPE_ADJUSTMENT,
                    'move_type' => $moveType,
                    'quantity' => abs($line->variance),
                    'transaction_date' => $this->adjustment_date,
                    'reference_document' => 'stock_adjustment',
                    'reference_number' => $this->adjustment_id,
                    'origin' => 'Stock Adjustment',
                    'state' => StockTransaction::STATE_DONE,
                    'notes' => $this->adjustment_reason
                ]);
            }
        }

        // Update the adjustment status
        $this->status = self::STATUS_COMPLETED;
        $this->save();

        return true;
    }

    /**
     * Submit for approval
     */
    public function submit()
    {
        if ($this->status != self::STATUS_DRAFT) {
            return false;
        }

        $this->status = self::STATUS_PENDING;
        $this->save();

        return true;
    }

    /**
     * Approve the adjustment
     */
    public function approve()
    {
        if ($this->status != self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->save();

        return true;
    }

    /**
     * Reject the adjustment
     */
    public function reject()
    {
        if ($this->status != self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->save();

        return true;
    }

    /**
     * Get summary information for the adjustment
     */
    public function getSummaryAttribute()
    {
        $lines = $this->adjustmentLines;
        
        return [
            'total_lines' => $lines->count(),
            'total_variance' => $lines->sum('variance'),
            'positive_adjustments' => $lines->where('variance', '>', 0)->count(),
            'negative_adjustments' => $lines->where('variance', '<', 0)->count(),
            'no_change' => $lines->where('variance', '=', 0)->count(),
        ];
    }
}