<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IAH_support_id
 * @property string $project_id
 * @property int|null $employed_at_st_ann
 * @property string|null $employment_details
 * @property int|null $received_support
 * @property string|null $support_details
 * @property int|null $govt_support
 * @property string|null $govt_support_nature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereEmployedAtStAnn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereEmploymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereGovtSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereGovtSupportNature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereIAHSupportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereReceivedSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereSupportDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIAHSupportDetails extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_support_details';

    protected $fillable = [
        'IAH_support_id',
        'project_id',
        'employed_at_st_ann',
        'employment_details',
        'received_support',
        'support_details',
        'govt_support',
        'govt_support_nature',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_support_id = $model->generateIAHSupportId();
        });
    }

    private function generateIAHSupportId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_support_id, -4)) + 1 : 1;
        return 'IAH-SUPPORT-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
