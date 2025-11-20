<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property string $IES_expense_id
 * @property string $particular
 * @property string $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\IES\ProjectIESExpenses $projectIESExpense
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIESExpenseDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_IES_expense_details';

    /**
     * The primary key for the model (if not "id").
     *
     * By default, Laravel expects 'id', but in this case,
     * we already have an 'id' column in the migration, so
     * leave this as is.
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'IES_expense_id',
        'particular',
        'amount'
    ];

    /**
     * Defines the relationship with the ProjectIESExpense model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function projectIESExpense()
    {
        return $this->belongsTo(
            ProjectIESExpenses::class,   // The related model
            'IES_expense_id',           // The foreign key on THIS table
            'IES_expense_id'            // The local key on the parent table
        );
    }
}
