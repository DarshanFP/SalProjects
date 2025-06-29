<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_family_member_id
 * @property string $project_id
 * @property string $iies_member_name
 * @property string $iies_work_nature
 * @property string $iies_monthly_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIIESFamilyMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIiesMemberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIiesMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIiesWorkNature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESFamilyWorkingMembers extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_family_working_members';

    protected $fillable = [
        'IIES_family_member_id',
        'project_id',
        'iies_member_name',
        'iies_work_nature',
        'iies_monthly_income',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IIES_family_member_id = $model->generateIIESFamilyMemberId();
        });
    }

    private function generateIIESFamilyMemberId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_family_member_id, -4)) + 1 : 1;
        return 'IIES-FAMMEM-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
