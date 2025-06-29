<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTPersonalCost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTPersonalCost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTPersonalCost query()
 * @mixin \Eloquent
 */
class ProjectRSTPersonalCost extends Model
{
    use HasFactory;

    protected $table = 'project_RST_personal_cost';

    protected $fillable = [
        'personal_cost_id',
        'project_id',
        'particular',
        'nr_staff',
        'rate',
        'year_1',
        'year_2',
        'year_3',
        'year_4'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->personal_cost_id = $model->generatePersonalCostId();
        });
    }

    private function generatePersonalCostId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->personal_cost_id, -4)) + 1 : 1;

        return 'RST-PC-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
