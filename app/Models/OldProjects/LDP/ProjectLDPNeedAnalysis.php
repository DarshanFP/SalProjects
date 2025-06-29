<?php

namespace App\Models\OldProjects\LDP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $LDP_need_analysis_id
 * @property string $project_id
 * @property string|null $document_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereLDPNeedAnalysisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectLDPNeedAnalysis extends Model
{
    use HasFactory;

    protected $table = 'project_LDP_need_analysis';

    protected $fillable = [
        'LDP_need_analysis_id',
        'project_id',
        'document_path', // For uploaded document
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->LDP_need_analysis_id = $model->generateLDPNeedAnalysisId();
        });
    }

    private function generateLDPNeedAnalysisId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->LDP_need_analysis_id, -4)) + 1 : 1;

        return 'LDP-NA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
