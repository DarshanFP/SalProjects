<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project; // Import the Project model for the relationship

class ProjectCCIPresentSituation extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_present_situation';

    protected $fillable = [
        'CCI_present_situation_id',
        'project_id',
        'internal_challenges',
        'external_challenges',
        'area_of_focus', // Area of focus for the current year
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_present_situation_id = $model->generateCCIPresentSituationId();
        });
    }

    private function generateCCIPresentSituationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_present_situation_id, -4)) + 1 : 1;

        return 'CCI-PS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
