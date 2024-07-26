<?php

namespace App\Models\OldProjects;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldDevelopmentProject extends Model
{
    use HasFactory;

    protected $table = 'oldDevelopmentProjects';

    protected $fillable = [
        'user_id',
        'project_title',
        'place',
        'society_name',
        'commencement_month_year',
        'in_charge',
        'total_beneficiaries',
        'reporting_period',
        'goal',
        'total_amount_sanctioned',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgets()
    {
        return $this->hasMany(OldDevelopmentProjectBudget::class, 'project_id');
    }

    public function attachments()
    {
        return $this->hasMany(OldDevelopmentProjectAttachment::class, 'project_id');
    }
}
