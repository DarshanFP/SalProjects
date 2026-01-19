<?php

namespace App\Models\Reports\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIReportValidationResult extends Model
{
    use HasFactory;

    protected $table = 'ai_report_validation_results';

    protected $fillable = [
        'report_type',
        'report_id',
        'validation_results',
        'overall_status',
        'data_quality_score',
        'overall_assessment',
        'inconsistencies_count',
        'missing_info_count',
        'unusual_patterns_count',
        'potential_errors_count',
        'ai_model_used',
        'ai_tokens_used',
        'validated_at',
    ];

    protected $casts = [
        'validation_results' => 'array',
        'validated_at' => 'datetime',
        'data_quality_score' => 'integer',
        'inconsistencies_count' => 'integer',
        'missing_info_count' => 'integer',
        'unusual_patterns_count' => 'integer',
        'potential_errors_count' => 'integer',
    ];

    /**
     * Get the report based on report_type
     */
    public function report()
    {
        switch ($this->report_type) {
            case 'monthly':
                return $this->belongsTo(\App\Models\Reports\Monthly\DPReport::class, 'report_id', 'report_id');
            case 'quarterly':
                return $this->belongsTo(\App\Models\Reports\Quarterly\QuarterlyReport::class, 'report_id');
            case 'half_yearly':
                return $this->belongsTo(\App\Models\Reports\HalfYearly\HalfYearlyReport::class, 'report_id');
            case 'annual':
                return $this->belongsTo(\App\Models\Reports\Annual\AnnualReport::class, 'report_id');
            default:
                return null;
        }
    }

    /**
     * Check if validation status is OK
     */
    public function isOk(): bool
    {
        return $this->overall_status === 'ok';
    }

    /**
     * Check if validation has warnings
     */
    public function hasWarnings(): bool
    {
        return $this->overall_status === 'warning';
    }

    /**
     * Check if validation has errors
     */
    public function hasErrors(): bool
    {
        return $this->overall_status === 'error';
    }

    /**
     * Get validation results as array (with fallback)
     */
    public function getValidationResultsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value ?? [];
    }

    /**
     * Get data quality assessment color class
     */
    public function getQualityColorClass(): string
    {
        $score = $this->data_quality_score ?? 0;

        if ($score >= 80) {
            return 'success'; // green
        } elseif ($score >= 60) {
            return 'warning'; // yellow
        } else {
            return 'danger'; // red
        }
    }

    /**
     * Get overall status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->overall_status) {
            'ok' => 'success',
            'warning' => 'warning',
            'error' => 'danger',
            default => 'secondary',
        };
    }
}
