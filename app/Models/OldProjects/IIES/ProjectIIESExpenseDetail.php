<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectIIESExpenseDetail extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_expense_details';

    protected $fillable = [
        'IIES_expense_id',
        'iies_particular',
        'iies_amount'
    ];

    public function projectIIESExpense()
    {
        return $this->belongsTo(ProjectIIESExpenses::class, 'IIES_expense_id', 'IIES_expense_id');
    }
}
