<?php
namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEduRUTTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_edu_rut_target_groups'; // Ensure the table name is correct

    protected $fillable = [
        'target_group_id',
        'project_id',
        'beneficiary_name',
        'caste',
        'institution_name',
        'class_standard',
        'total_tuition_fee',
        'eligibility_scholarship',
        'expected_amount',
        'contribution_from_family',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->target_group_id = $model->generateTargetGroupId();
        });
    }

    private function generateTargetGroupId()
    {
        $latestGroup = self::latest('id')->first();
        $sequenceNumber = $latestGroup ? intval(substr($latestGroup->target_group_id, -4)) + 1 : 1;

        return 'TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
