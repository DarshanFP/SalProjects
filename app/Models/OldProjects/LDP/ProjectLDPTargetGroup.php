<?php

namespace App\Models\OldProjects\LDP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $LDP_target_group_id
 * @property string $project_id
 * @property string|null $L_beneficiary_name
 * @property string|null $L_family_situation
 * @property string|null $L_nature_of_livelihood
 * @property int|null $L_amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLDPTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLNatureOfLivelihood($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectLDPTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_LDP_target_group';

    protected $fillable = [
        'LDP_target_group_id',
        'project_id',
        'L_beneficiary_name',
        'L_family_situation',
        'L_nature_of_livelihood',
        'L_amount_requested',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->LDP_target_group_id = $model->generateLDPTargetGroupId();
        });
    }

    private function generateLDPTargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->LDP_target_group_id, -4)) + 1 : 1;

        return 'LDP-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
