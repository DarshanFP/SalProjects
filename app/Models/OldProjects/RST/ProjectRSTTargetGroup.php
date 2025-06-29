<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $RST_target_group_id
 * @property string $project_id
 * @property int|null $tg_no_of_beneficiaries
 * @property string|null $beneficiaries_description_problems
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereBeneficiariesDescriptionProblems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereRSTTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereTgNoOfBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectRSTTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_RST_target_group';

    protected $fillable = [
        'RST_target_group_id',
        'project_id',
        'tg_no_of_beneficiaries',
        'beneficiaries_description_problems'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->RST_target_group_id = $model->generateTargetGroupId();
        });
    }

    private function generateTargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->RST_target_group_id, -4)) + 1 : 1;

        return 'RST-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
