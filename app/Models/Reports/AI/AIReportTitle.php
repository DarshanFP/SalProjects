<?php

namespace App\Models\Reports\AI;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIReportTitle extends Model
{
    use HasFactory;

    protected $table = 'ai_report_titles';

    protected $fillable = [
        'report_type',
        'report_id',
        'report_title',
        'section_headings',
        'ai_model_used',
        'ai_tokens_used',
        'generated_at',
        'last_edited_at',
        'last_edited_by_user_id',
        'is_edited',
    ];

    protected $casts = [
        'section_headings' => 'array',
        'generated_at' => 'datetime',
        'last_edited_at' => 'datetime',
        'is_edited' => 'boolean',
    ];

    /**
     * Polymorphic relationship helper to get the report
     */
    public function report()
    {
        switch ($this->report_type) {
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
     * Get the user who last edited this title
     */
    public function lastEditedBy()
    {
        return $this->belongsTo(User::class, 'last_edited_by_user_id');
    }

    /**
     * Mark the title as edited
     */
    public function markAsEdited($userId = null)
    {
        $this->update([
            'is_edited' => true,
            'last_edited_at' => now(),
            'last_edited_by_user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Check if this title has been edited
     */
    public function hasBeenEdited(): bool
    {
        return $this->is_edited;
    }

    /**
     * Get section headings as array (with fallback)
     */
    public function getSectionHeadingsAttribute($value)
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
     * Get a specific section heading
     */
    public function getSectionHeading($key, $default = null)
    {
        $headings = $this->section_headings ?? [];
        return $headings[$key] ?? $default;
    }

    /**
     * Set a specific section heading
     */
    public function setSectionHeading($key, $value)
    {
        $headings = $this->section_headings ?? [];
        $headings[$key] = $value;
        $this->section_headings = $headings;
        $this->markAsEdited();
    }
}
