<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
