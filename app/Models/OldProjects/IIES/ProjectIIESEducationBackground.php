<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIIESEducationBackground extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_education_background';

    protected $fillable = [
        'IIES_education_id',
        'project_id',
        'prev_education',
        'prev_institution',
        'prev_insti_address',
        'prev_marks',
        'current_studies',
        'curr_institution',
        'curr_insti_address',
        'aspiration',
        'long_term_effect'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IIES_education_id = $model->generateIIESEducationId();
        });
    }

    private function generateIIESEducationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_education_id, -4)) + 1 : 1;
        return 'IIES-EDU-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
