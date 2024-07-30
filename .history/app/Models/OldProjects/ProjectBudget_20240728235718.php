<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase',
        'particular',
        'rate_quantity',
        'rate_multiplier',
        'rate_duration',
        'rate_increase',
        'this_phase',
        'next_phase'
    ];

    // public function project()
    // {
    //     return $this->belongsTo(Project::class);
    // }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    // for calculation of total amount
}
