<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IGE_ongoing_bnfcry_id
 * @property string $project_id
 * @property string|null $obeneficiary_name
 * @property string|null $ocaste
 * @property string|null $oaddress
 * @property string|null $ocurrent_group_year_of_study
 * @property string|null $operformance_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereIGEOngoingBnfcryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOaddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereObeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOcaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOcurrentGroupYearOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOperformanceDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIGEOngoingBeneficiaries extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_ongoing_beneficiaries';

    protected $fillable = [
        'IGE_ongoing_bnfcry_id',
        'project_id',
        'obeneficiary_name',
        'ocaste',
        'oaddress',
        'ocurrent_group_year_of_study',
        'operformance_details'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_ongoing_bnfcry_id = $model->generateIGEOngoingBeneficiariesId();
        });
    }

    private function generateIGEOngoingBeneficiariesId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_ongoing_bnfcry_id, -4)) + 1 : 1;
        return 'IGE-ONGB-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
