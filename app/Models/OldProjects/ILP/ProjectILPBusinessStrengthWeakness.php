<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
