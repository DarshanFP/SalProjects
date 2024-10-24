<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIGEInstitutionInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_institution_info';

    protected $fillable = [
        'IGE_institution_id',
        'project_id',
        'institutional_type',
        'age_group',
        'previous_year_beneficiaries',
        'outcome_impact'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_institution_id = $model->generateIGEInstitutionId();
        });
    }

    private function generateIGEInstitutionId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_institution_id, -4)) + 1 : 1;
        return 'IGE-INST-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
