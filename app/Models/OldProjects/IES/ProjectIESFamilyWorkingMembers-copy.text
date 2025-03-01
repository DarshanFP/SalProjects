<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
