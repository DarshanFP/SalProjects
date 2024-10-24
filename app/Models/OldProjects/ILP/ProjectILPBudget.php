<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectILPBudget extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_budget';

    protected $fillable = [
        'ILP_budget_id', 'project_id', 'budget_desc', 'cost', 'beneficiary_contribution', 'amount_requested'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_budget_id = $model->generateILPBudgetId();
        });
    }

    private function generateILPBudgetId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_budget_id, -4)) + 1 : 1;
        return 'ILP-BUD-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
