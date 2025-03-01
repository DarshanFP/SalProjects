<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
