<?php

namespace App\Models\OldProjects\RST;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $DPRST_bnfcrs_area_id
 * @property string $project_id
 * @property string|null $project_area
 * @property string|null $category_beneficiary
 * @property int|null $direct_beneficiaries
 * @property int|null $indirect_beneficiaries
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereCategoryBeneficiary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereDPRSTBnfcrsAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereDirectBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereIndirectBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereProjectArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectDPRSTBeneficiariesArea extends Model
{
    protected $table = 'project_RST_DP_beneficiaries_area';
    


    protected $fillable = [
        'project_area',
        'category_beneficiary',
        'direct_beneficiaries',
        'indirect_beneficiaries',
        'project_id',
        'DPRST_bnfcrs_area_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->DPRST_bnfcrs_area_id = $model->generateBeneficiariesAreaId();
        });
    }

    private function generateBeneficiariesAreaId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->DPRST_bnfcrs_area_id, -4)) + 1 : 1;

        return 'RST-BA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
