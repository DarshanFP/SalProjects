<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueGoals newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueGoals newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueGoals query()
 * @mixin \Eloquent
 */
class ProjectILPRevenueGoals extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_revenue_goals';

    protected $fillable = [
        'ILP_revenue_id', 'project_id', 'business_plan_items', 'annual_income', 'annual_expenses'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_revenue_id = $model->generateILPRevenueId();
        });
    }

    private function generateILPRevenueId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_revenue_id, -4)) + 1 : 1;
        return 'ILP-REV-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
