<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IAH_earning_id
 * @property string $project_id
 * @property string|null $member_name
 * @property string|null $work_type
 * @property string|null $monthly_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereIAHEarningId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereMemberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereWorkType($value)
 * @mixin \Eloquent
 */
class ProjectIAHEarningMembers extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_earning_members';

    protected $fillable = [
        'IAH_earning_id',
        'project_id',
        'member_name',
        'work_type',
        'monthly_income',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_earning_id = $model->generateIAHEarningId();
        });
    }

    private function generateIAHEarningId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_earning_id, -4)) + 1 : 1;
        return 'IAH-EARN-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
