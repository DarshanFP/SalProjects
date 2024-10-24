<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIGEBudget extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_budget';

    protected $fillable = [
        'IGE_budget_id',
        'project_id',
        'name',
        'study_proposed',
        'college_fees',
        'hostel_fees',
        'total_amount',
        'scholarship_eligibility',
        'family_contribution',
        'amount_requested',
        'total_amount_requested'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_budget_id = $model->generateIGEBudgetId();
        });
    }

    private function generateIGEBudgetId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_budget_id, -4)) + 1 : 1;
        return 'IGE-BUDG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
