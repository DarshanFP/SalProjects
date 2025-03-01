<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIESExpenses extends Model
{
    use HasFactory;

    protected $table = 'project_IES_expenses';

    protected $fillable = [
        'IES_expense_id',
        'project_id',
        'total_expenses',
        'expected_scholarship_govt',
        'support_other_sources',
        'beneficiary_contribution',
        'balance_requested'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_expense_id = $model->generateIESExpenseId();
        });
    }

    private function generateIESExpenseId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_expense_id, -4)) + 1 : 1;
        return 'IES-EXP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function expenseDetails()
{
    return $this->hasMany(ProjectIESExpenseDetail::class, 'IES_expense_id', 'IES_expense_id');
}



}
