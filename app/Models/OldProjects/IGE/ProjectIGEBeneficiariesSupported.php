<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IGE_bnfcry_supprtd_id
 * @property string $project_id
 * @property string|null $class
 * @property int|null $total_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereIGEBnfcrySupprtdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereTotalNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIGEBeneficiariesSupported extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_beneficiaries_supported';

    protected $fillable = [
        'IGE_bnfcry_supprtd_id',
        'project_id',
        'class',
        'total_number'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_bnfcry_supprtd_id = $model->generateIGEBeneficiariesSupportedId();
        });
    }

    private function generateIGEBeneficiariesSupportedId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_bnfcry_supprtd_id, -4)) + 1 : 1;
        return 'IGE-BSUP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
