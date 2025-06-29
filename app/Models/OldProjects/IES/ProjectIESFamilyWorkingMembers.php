<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IES_family_member_id
 * @property string $project_id
 * @property string|null $member_name
 * @property string|null $work_nature
 * @property string|null $monthly_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereIESFamilyMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereMemberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereWorkNature($value)
 * @mixin \Eloquent
 */
class ProjectIESFamilyWorkingMembers extends Model
{
    use HasFactory;

    protected $table = 'project_IES_family_working_members';

    protected $fillable = [
        'IES_family_member_id',
        'project_id',
        'member_name',
        'work_nature',
        'monthly_income'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_family_member_id = $model->generateIESFamilyMemberId();
        });
    }

    private function generateIESFamilyMemberId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_family_member_id, -4)) + 1 : 1;
        return 'IES-FAMMEM-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
