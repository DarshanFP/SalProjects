<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIAHBudgetDetails extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_budget_details';

    protected $fillable = [
        'IAH_budget_id',
        'project_id',
        'particular',
        'amount',
        'total_expenses',
        'family_contribution',
        'amount_requested',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_budget_id = $model->generateIAHBudgetId();
        });
    }

    private function generateIAHBudgetId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_budget_id, -4)) + 1 : 1;
        return 'IAH-BUDG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
