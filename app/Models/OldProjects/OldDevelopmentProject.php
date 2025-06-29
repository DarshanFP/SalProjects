<?php

namespace App\Models\OldProjects;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $project_title
 * @property string $place
 * @property string $society_name
 * @property string $commencement_month_year
 * @property string $in_charge
 * @property int $total_beneficiaries
 * @property string $reporting_period
 * @property string $goal
 * @property string|null $total_amount_sanctioned
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\OldDevelopmentProjectAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\OldDevelopmentProjectBudget> $budgets
 * @property-read int|null $budgets_count
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject query()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereReportingPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereTotalAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereUserId($value)
 * @mixin \Eloquent
 */
class OldDevelopmentProject extends Model
{
    use HasFactory;

    protected $table = 'oldDevelopmentProjects';

    protected $fillable = [
        'user_id',
        'project_title',
        'place',
        'society_name',
        'commencement_month_year',
        'in_charge',
        'total_beneficiaries',
        'reporting_period',
        'goal',
        'total_amount_sanctioned',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgets()
    {
        return $this->hasMany(OldDevelopmentProjectBudget::class, 'project_id');
    }

    public function attachments()
    {
        return $this->hasMany(OldDevelopmentProjectAttachment::class, 'project_id');
    }
}
