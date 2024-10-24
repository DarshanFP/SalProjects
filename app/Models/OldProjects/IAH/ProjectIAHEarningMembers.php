<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIAHEarningMembers extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_earning_members';

    protected $fillable = [
        'IAH_earning_id',
        'project_id',
        'member_name',
        'work_type',
        'monthly_income',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_earning_id = $model->generateIAHEarningId();
        });
    }

    private function generateIAHEarningId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_earning_id, -4)) + 1 : 1;
        return 'IAH-EARN-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
