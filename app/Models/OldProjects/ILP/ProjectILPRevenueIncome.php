<?php

namespace App\Models\OldProjects\ILP;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_revenue_income_id
 * @property string $project_id
 * @property string $description
 * @property string|null $year_1
 * @property string|null $year_2
 * @property string|null $year_3
 * @property string|null $year_4
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereILPRevenueIncomeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear4($value)
 * @mixin \Eloquent
 */
class ProjectILPRevenueIncome extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_revenue_income';

    protected $fillable = [
        'ILP_revenue_income_id',
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
            $model->ILP_revenue_income_id = $model->generateILPRevenueIncomeId();
        });
    }

    private function generateILPRevenueIncomeId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_revenue_income_id, -4)) + 1 : 1;
        return 'ILP-INCOME-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
