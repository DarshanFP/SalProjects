<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
