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

    // for calculation of total amount of a particular phase

    public function dpAccountDetails()
    {
        return $this->hasMany(DPAccountDetail::class, 'project_id', 'project_id');
    }

    public function calculateTotalBudget()
    {
        return $this->rate_quantity * $this->rate_multiplier * $this->rate_duration * $this->rate_increase;
    }

    public function calculateRemainingBalance()
    {
        $totalExpenses = $this->dpAccountDetails()->sum('total_expenses');
        return $this->calculateTotalBudget() - $totalExpenses;
    }
}
