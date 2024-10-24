<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project; // Import the Project model for the relationship

class ProjectCCIAchievements extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_achievements';

    protected $fillable = [
        'CCI_achievements_id',
        'project_id',
        'academic_achievements',
        'sport_achievements',
        'other_achievements',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_achievements_id = $model->generateCCIAchievementsId();
        });
    }

    private function generateCCIAchievementsId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_achievements_id, -4)) + 1 : 1;

        return 'CCI-ACH-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
