<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTFinancialSummary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTFinancialSummary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTFinancialSummary query()
 * @mixin \Eloquent
 */
class ProjectRSTFinancialSummary extends Model
{
    use HasFactory;

    protected $table = 'project_RST_financial_summary';

    protected $fillable = [
        'financial_summary_id',
        'project_id',
        'year_1',
        'year_2',
        'year_3',
        'year_4',
        'local_contribution',
        'amount_requested'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->financial_summary_id = $model->generateFinancialSummaryId();
        });
    }

    private function generateFinancialSummaryId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->financial_summary_id, -4)) + 1 : 1;

        return 'RST-FS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
