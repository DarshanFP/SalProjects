<?php

namespace App\Models;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 6: Immutable audit log for admin budget corrections.
 * Do not allow updates or deletes; append-only.
 *
 * @see Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md §10 Phase 6a
 */
class BudgetCorrectionAudit extends Model
{
    public const ACTION_ACCEPT_SUGGESTED = 'accept_suggested';
    public const ACTION_MANUAL_CORRECTION = 'manual_correction';
    public const ACTION_REJECT = 'reject';

    protected $table = 'budget_correction_audit';

    protected $fillable = [
        'project_id',
        'project_type',
        'admin_user_id',
        'user_role',
        'action_type',
        'old_overall',
        'old_forwarded',
        'old_local',
        'old_sanctioned',
        'old_opening',
        'new_overall',
        'new_forwarded',
        'new_local',
        'new_sanctioned',
        'new_opening',
        'admin_comment',
        'ip_address',
    ];

    protected $casts = [
        'old_overall' => 'float',
        'old_forwarded' => 'float',
        'old_local' => 'float',
        'old_sanctioned' => 'float',
        'old_opening' => 'float',
        'new_overall' => 'float',
        'new_forwarded' => 'float',
        'new_local' => 'float',
        'new_sanctioned' => 'float',
        'new_opening' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id', 'id');
    }

    /**
     * Get old values as array (for display).
     */
    public function getOldValuesAttribute(): array
    {
        return [
            'overall_project_budget' => (float) ($this->old_overall ?? 0),
            'amount_forwarded' => (float) ($this->old_forwarded ?? 0),
            'local_contribution' => (float) ($this->old_local ?? 0),
            'amount_sanctioned' => (float) ($this->old_sanctioned ?? 0),
            'opening_balance' => (float) ($this->old_opening ?? 0),
        ];
    }

    /**
     * Get new values as array (for display).
     */
    public function getNewValuesAttribute(): array
    {
        return [
            'overall_project_budget' => (float) ($this->new_overall ?? 0),
            'amount_forwarded' => (float) ($this->new_forwarded ?? 0),
            'local_contribution' => (float) ($this->new_local ?? 0),
            'amount_sanctioned' => (float) ($this->new_sanctioned ?? 0),
            'opening_balance' => (float) ($this->new_opening ?? 0),
        ];
    }
}
