<?php

namespace App\Models\OldProjects\ILP;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_revenue_expenses_id
 * @property string $project_id
 * @property string $description
 * @property string|null $year_1
 * @property string|null $year_2
 * @property string|null $year_3
 * @property string|null $year_4
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereILPRevenueExpensesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear4($value)
 * @mixin \Eloquent
 */
class ProjectILPRevenueExpense extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_revenue_expenses';

    protected $fillable = [
        'ILP_revenue_expenses_id',
        'project_id',
        'description',
        'year_1',
        'year_2',
        'year_3',
        'year_4',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_revenue_expenses_id = $model->generateILPRevenueExpensesId();
        });
    }

    private function generateILPRevenueExpensesId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_revenue_expenses_id, -4)) + 1 : 1;
        return 'ILP-EXP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
