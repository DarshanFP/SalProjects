<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectRSTGeographicalArea extends Model
{
    use HasFactory;

    protected $table = 'project_RST_geographical_areas';

    protected $fillable = [
        'geographical_area_id',
        'project_id',
        'mandal',
        'villages',
        'town',
        'no_of_beneficiaries'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->geographical_area_id = $model->generateGeographicalAreaId();
        });
    }

    private function generateGeographicalAreaId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->geographical_area_id, -4)) + 1 : 1;

        return 'RST-GEO-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
