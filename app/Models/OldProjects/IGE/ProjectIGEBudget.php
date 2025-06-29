<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IGE_budget_id
 * @property string $project_id
 * @property string|null $name
 * @property string|null $study_proposed
 * @property string|null $college_fees
 * @property string|null $hostel_fees
 * @property string|null $total_amount
 * @property string|null $scholarship_eligibility
 * @property string|null $family_contribution
 * @property string|null $amount_requested
 * @property string|null $total_amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereCollegeFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereFamilyContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereHostelFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereIGEBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereScholarshipEligibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereStudyProposed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereTotalAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIGEBudget extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_budget';

    protected $fillable = [
        'IGE_budget_id',
        'project_id',
        'name',
        'study_proposed',
        'college_fees',
        'hostel_fees',
        'total_amount',
        'scholarship_eligibility',
        'family_contribution',
        'amount_requested',
        'total_amount_requested'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_budget_id = $model->generateIGEBudgetId();
        });
    }

    private function generateIGEBudgetId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_budget_id, -4)) + 1 : 1;
        return 'IGE-BUDG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
