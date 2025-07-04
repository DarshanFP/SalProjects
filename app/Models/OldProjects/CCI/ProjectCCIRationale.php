<?php

namespace App\Models\OldProjects\CCI;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $CCI_rationale_id
 * @property string $project_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereCCIRationaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectCCIRationale extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_rationale';

    protected $fillable = [
        'CCI_rationale_id',
        'project_id',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_rationale_id = $model->generateCCIRationaleId();
        });
    }

    private function generateCCIRationaleId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_rationale_id, -4)) + 1 : 1;

        return 'CCI-RN-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
