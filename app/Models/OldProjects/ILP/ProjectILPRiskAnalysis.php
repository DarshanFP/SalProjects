<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectILPRiskAnalysis extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_risk_analysis';

    protected $fillable = [
        'ILP_risk_id', 'project_id', 'identified_risks', 'mitigation_measures', 'business_sustainability', 'expected_profits'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_risk_id = $model->generateILPRiskId();
        });
    }

    private function generateILPRiskId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_risk_id, -4)) + 1 : 1;
        return 'ILP-RISK-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
