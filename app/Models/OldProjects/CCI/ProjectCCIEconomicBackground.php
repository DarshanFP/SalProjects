<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $CCI_eco_bg_id
 * @property string $project_id
 * @property int|null $agricultural_labour_number
 * @property int|null $marginal_farmers_number
 * @property int|null $self_employed_parents_number
 * @property int|null $informal_sector_parents_number
 * @property int|null $any_other_number
 * @property string|null $general_remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereAgriculturalLabourNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereAnyOtherNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereCCIEcoBgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereGeneralRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereInformalSectorParentsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereMarginalFarmersNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereSelfEmployedParentsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectCCIEconomicBackground extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_economic_background';
    // Specify primary key and its type
    protected $primaryKey = 'CCI_eco_bg_id';
    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'CCI_eco_bg_id',
        'project_id',
        'agricultural_labour_number',
        'marginal_farmers_number',
        'self_employed_parents_number',
        'informal_sector_parents_number',
        'any_other_number',
        'general_remarks', // Added field for general remarks
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_eco_bg_id = $model->generateCCIEconomicBackgroundId();
        });
    }

    private function generateCCIEconomicBackgroundId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_eco_bg_id, -4)) + 1 : 1;

        return 'CCI-EB-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }


    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
