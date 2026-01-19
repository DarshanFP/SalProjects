<?php

namespace App\Models\Reports\Aggregated;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AggregatedReportObjective extends Model
{
    use HasFactory;

    protected $table = 'aggregated_report_objectives';

    protected $fillable = [
        'report_type',
        'report_id',
        'objective_text',
        'cumulative_progress',
        'monthly_breakdown',
        'project_objective_id',
    ];

    protected $casts = [
        'monthly_breakdown' => 'array',
        'project_objective_id' => 'integer',
    ];

    // Polymorphic relationship helper methods
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

    public function projectObjective()
    {
        return $this->belongsTo(\App\Models\OldProjects\ProjectObjective::class, 'project_objective_id');
    }
}
