<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldDevelopmentProjectBudget extends Model
{
    use HasFactory;

    protected $table = 'old_DP_budgets';

    protected $fillable = [
        'project_id',
        'phase',
        'description',
        'rate_quantity',
        'rate_multiplier',
        'rate_duration',
        'rate_increase',
        'this_phase',
        'next_phase',
    ];

    public function project()
    {
        return $this->belongsTo(OldDevelopmentProject::class, 'project_id');
    }
}
