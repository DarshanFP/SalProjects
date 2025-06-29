<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_fin_sup_id
 * @property string $project_id
 * @property int $govt_eligible_scholarship
 * @property string|null $scholarship_amt
 * @property int $other_eligible_scholarship
 * @property string|null $other_scholarship_amt
 * @property string|null $family_contrib
 * @property string|null $no_contrib_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereFamilyContrib($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereGovtEligibleScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereIIESFinSupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereNoContribReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereOtherEligibleScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereOtherScholarshipAmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereScholarshipAmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESScopeFinancialSupport extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_scope_financial_support';

    protected $fillable = [
        'IIES_fin_sup_id',
        'project_id',
        'govt_eligible_scholarship',
        'scholarship_amt',
        'other_eligible_scholarship',
        'other_scholarship_amt',
        'family_contrib',
        'no_contrib_reason'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IIES_fin_sup_id = $model->generateIIESFinancialSupportId();
        });
    }

    private function generateIIESFinancialSupportId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_fin_sup_id, -4)) + 1 : 1;
        return 'IIES-FS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
