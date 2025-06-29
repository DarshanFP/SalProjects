<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project; // Import the Project model for the relationship

/**
 * 
 *
 * @property int $id
 * @property string $CCI_achievements_id
 * @property string $project_id
 * @property string|null $academic_achievements
 * @property string|null $sport_achievements
 * @property string|null $other_achievements
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereAcademicAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereCCIAchievementsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereOtherAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereSportAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
