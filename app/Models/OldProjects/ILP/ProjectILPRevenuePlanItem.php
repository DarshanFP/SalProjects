<?php

namespace App\Models\OldProjects\ILP;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
