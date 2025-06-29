<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_expense_id
 * @property string $iies_particular
 * @property string $iies_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESExpenses|null $projectIIESExpense
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereIIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereIiesAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereIiesParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
