<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_risk_id
 * @property string $project_id
 * @property string|null $identified_risks
 * @property string|null $mitigation_measures
 * @property string|null $business_sustainability
 * @property string|null $expected_profits
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereBusinessSustainability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereExpectedProfits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereILPRiskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereIdentifiedRisks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereMitigationMeasures($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
