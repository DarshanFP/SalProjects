<?php

namespace App\Models\Reports\AI;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIReportInsight extends Model
{
    use HasFactory;

    protected $table = 'ai_report_insights';

    protected $fillable = [
        'report_type',
        'report_id',
        'executive_summary',
        'key_achievements',
        'progress_trends',
        'challenges',
        'recommendations',
        'strategic_insights',
        'quarterly_comparison',
        'impact_assessment',
        'budget_performance',
        'future_outlook',
        'year_over_year_comparison',
        'ai_model_used',
        'ai_tokens_used',
        'generated_at',
        'last_edited_at',
        'last_edited_by_user_id',
        'is_edited',
    ];

    protected $casts = [
        'key_achievements' => 'array',
        'progress_trends' => 'array',
        'challenges' => 'array',
        'recommendations' => 'array',
        'strategic_insights' => 'array',
        'quarterly_comparison' => 'array',
        'impact_assessment' => 'array',
        'budget_performance' => 'array',
        'future_outlook' => 'array',
        'year_over_year_comparison' => 'array',
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
     * Get the user who last edited this insight
     */
    public function lastEditedBy()
    {
        return $this->belongsTo(User::class, 'last_edited_by_user_id');
    }

    /**
     * Mark the insight as edited
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
     * Check if this insight has been edited
     */
    public function hasBeenEdited(): bool
    {
        return $this->is_edited;
    }

    /**
     * Get key achievements as array (with fallback)
     */
    public function getKeyAchievementsAttribute($value)
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
     * Get recommendations as array (with fallback)
     */
    public function getRecommendationsAttribute($value)
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
     * Get challenges as array (with fallback)
     */
    public function getChallengesAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return $value ?? [];
    }
}
