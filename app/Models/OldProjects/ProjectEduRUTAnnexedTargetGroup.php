<?php
namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEduRUTAnnexedTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_edu_rut_annexed_target_groups'; // Ensure this matches the table in your DB

    protected $fillable = [
        'annexed_target_group_id',
        'project_id',
        'beneficiary_name',
        'family_background',
        'need_of_support',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->annexed_target_group_id = $model->generateAnnexedTargetGroupId();
        });
    }

    private function generateAnnexedTargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->annexed_target_group_id, -4)) + 1 : 1;

        return 'ANNX-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
