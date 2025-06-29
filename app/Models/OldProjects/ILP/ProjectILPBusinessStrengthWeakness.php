<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_strength_id
 * @property string $project_id
 * @property string|null $strengths
 * @property string|null $weaknesses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereILPStrengthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereWeaknesses($value)
 * @mixin \Eloquent
 */
class ProjectILPBusinessStrengthWeakness extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_strength_weakness';

    protected $fillable = [
        'ILP_strength_id', 'project_id', 'strengths', 'weaknesses'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_strength_id = $model->generateILPStrengthId();
        });
    }

    private function generateILPStrengthId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_strength_id, -4)) + 1 : 1;
        return 'ILP-STR-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
