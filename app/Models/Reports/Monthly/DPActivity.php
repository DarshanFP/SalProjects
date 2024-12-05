<?php

namespace App\Models\Reports\Monthly;

use App\Models\OldProjects\ProjectTimeframe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DPActivity extends Model
{
    use HasFactory;

    protected $table = 'DP_Activities';
    protected $primaryKey = 'activity_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'activity_id',
        'objective_id',
        'project_activity_id',
        'activity',
        'month',
        'summary_activities',
        'qualitative_quantitative_data',
        'intermediate_outcomes',
    ];

    public function objective()
    {
        return $this->belongsTo(DPObjective::class, 'objective_id', 'objective_id');
    }
    public function timeframes()
    {
        return $this->hasMany(ProjectTimeframe::class, 'activity_id', 'project_activity_id');
    }




}
