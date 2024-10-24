<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectRSTInstitutionInfo extends Model
{
    use HasFactory;

    protected $table = 'project_RST_institution_info';

    protected $fillable = [
        'RST_institution_id',
        'project_id',
        'year_setup',
        'total_students_trained',
        'beneficiaries_last_year',
        'training_outcome'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->RST_institution_id = $model->generateInstitutionId();
        });
    }

    private function generateInstitutionId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->RST_institution_id, -4)) + 1 : 1;

        return 'RST-INS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
