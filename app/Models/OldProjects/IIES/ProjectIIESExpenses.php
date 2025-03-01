<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIIESExpenses extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_expenses';

    protected $fillable = [
        'IIES_expense_id',
        'project_id',
        'iies_total_expenses',
        'iies_expected_scholarship_govt',
        'iies_support_other_sources',
        'iies_beneficiary_contribution',
        'iies_balance_requested'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IIES_expense_id = $model->generateIIESExpenseId();
        });
    }

    private function generateIIESExpenseId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_expense_id, -4)) + 1 : 1;
        return 'IIES-EXP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function expenseDetails()
    {
        return $this->hasMany(ProjectIIESExpenseDetail::class, 'IIES_expense_id', 'IIES_expense_id');
    }
}
