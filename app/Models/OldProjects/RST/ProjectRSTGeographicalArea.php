<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $geographical_area_id
 * @property string $project_id
 * @property string|null $mandal
 * @property string|null $villages
 * @property string|null $town
 * @property int|null $no_of_beneficiaries
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereGeographicalAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereMandal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereNoOfBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereTown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereVillages($value)
 * @mixin \Eloquent
 */
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
