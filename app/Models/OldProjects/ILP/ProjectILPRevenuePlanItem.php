<?php

namespace App\Models\OldProjects\ILP;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_revenue_plan_id
 * @property string $project_id
 * @property string $item
 * @property string|null $year_1
 * @property string|null $year_2
 * @property string|null $year_3
 * @property string|null $year_4
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereILPRevenuePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear4($value)
 * @mixin \Eloquent
 */
class ProjectILPRevenuePlanItem extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_revenue_plan_items';

    protected $fillable = [
        'ILP_revenue_plan_id',
        'project_id',
        'item',
        'year_1',
        'year_2',
        'year_3',
        'year_4',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_revenue_plan_id = $model->generateILPRevenuePlanId();
        });
    }

    private function generateILPRevenuePlanId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_revenue_plan_id, -4)) + 1 : 1;
        return 'ILP-PLAN-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
