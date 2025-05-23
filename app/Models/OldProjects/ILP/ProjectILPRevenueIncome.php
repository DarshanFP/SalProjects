<?php

namespace App\Models\OldProjects\ILP;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
