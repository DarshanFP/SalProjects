<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'project_type',
        'project_title',
        'society_name',
        'president_name',
        'in_charge',
        'in_charge_name',
        'in_charge_mobile',
        'in_charge_email',
        'executor_name',
        'executor_mobile',
        'executor_email',
        'full_address',
        'overall_project_period',
        'current_phase',
        'commencement_month_year',
        'overall_project_budget',
        'amount_forwarded',
        'amount_sanctioned',
        'opening_balance',
        'coordinator_india_name',
        'coordinator_india_phone',
        'coordinator_india_email',
        'coordinator_luzern_name',
        'coordinator_luzern_phone',
        'coordinator_luzern_email',
        'goal',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->project_id = $model->generateProjectId();
        });
    }

    private function generateProjectId()
    {
        $initialsMap = [
            'CHILD CARE INSTITUTION' => 'CCI',
            'Development Projects' => 'DP',
            'Rural-Urban-Tribal' => 'RUT',
            'Institutional Ongoing Group Educational proposal' => 'IOGEP',
            'Livelihood Development Projects' => 'LDP',
            'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'CIC',
            'NEXT PHASE - DEVELOPMENT PROPOSAL' => 'NPD',
            'Residential Skill Training Proposal 2' => 'RSTP2',
            'Individual - Ongoing Educational support' => 'IOES',
            'Individual - Livelihood Application' => 'ILA',
            'Individual - Access to Health' => 'IAH',
            'Individual - Initial - Educational support' => 'IIES',
        ];

        $initials = $initialsMap[$this->project_type] ?? 'GEN';

        $latestProject = self::where('project_id', 'like', $initials . '-%')->latest('id')->first();
        $sequenceNumber = $latestProject ? intval(substr($latestProject->project_id, strlen($initials) + 1)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

        return $initials . '-' . $sequenceNumberPadded;
    }

    public function budgets()
    {
        return $this->hasMany(ProjectBudget::class, 'project_id', 'project_id');
    }

    public function attachments()
    {
        return $this->hasMany(ProjectAttachment::class, 'project_id', 'project_id');
    }
}
