<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
