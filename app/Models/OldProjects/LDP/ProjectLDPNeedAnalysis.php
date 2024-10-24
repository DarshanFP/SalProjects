<?php

namespace App\Models\OldProjects\LDP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
