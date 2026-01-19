<?php

namespace App\Models\Reports\Aggregated;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AggregatedReportPhoto extends Model
{
    use HasFactory;

    protected $table = 'aggregated_report_photos';

    protected $fillable = [
        'report_type',
        'report_id',
        'photo_path',
        'description',
        'source_monthly_report_id',
        'source_month',
        'source_year',
    ];

    protected $casts = [
        'source_month' => 'integer',
        'source_year' => 'integer',
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

    public function sourceMonthlyReport()
    {
        return $this->belongsTo(DPReport::class, 'source_monthly_report_id', 'report_id');
    }

    public function getSourcePeriodLabel()
    {
        if ($this->source_month && $this->source_year) {
            $monthName = date('F', mktime(0, 0, 0, $this->source_month, 1));
            return $monthName . ' ' . $this->source_year;
        }
        return 'N/A';
    }
}
