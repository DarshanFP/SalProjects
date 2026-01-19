<?php

namespace App\Models\Reports\Quarterly;

use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarterlyReport extends Model
{
    use HasFactory;

    protected $table = 'quarterly_reports';
    protected $primaryKey = 'report_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Status constants (same as monthly reports)
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED_TO_PROVINCIAL = 'submitted_to_provincial';
    public const STATUS_REVERTED_BY_PROVINCIAL = 'reverted_by_provincial';
    public const STATUS_FORWARDED_TO_COORDINATOR = 'forwarded_to_coordinator';
    public const STATUS_REVERTED_BY_COORDINATOR = 'reverted_by_coordinator';
    public const STATUS_APPROVED_BY_COORDINATOR = 'approved_by_coordinator';
    public const STATUS_REJECTED_BY_COORDINATOR = 'rejected_by_coordinator';

    // General user acting as Coordinator
    public const STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR = 'approved_by_general_as_coordinator';
    public const STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR = 'reverted_by_general_as_coordinator';

    // General user acting as Provincial
    public const STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL = 'approved_by_general_as_provincial';
    public const STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL = 'reverted_by_general_as_provincial';

    // Granular revert statuses
    public const STATUS_REVERTED_TO_EXECUTOR = 'reverted_to_executor';
    public const STATUS_REVERTED_TO_APPLICANT = 'reverted_to_applicant';
    public const STATUS_REVERTED_TO_PROVINCIAL = 'reverted_to_provincial';
    public const STATUS_REVERTED_TO_COORDINATOR = 'reverted_to_coordinator';

    // Status labels for display
    public static $statusLabels = [
        'draft' => 'Draft',
        'submitted_to_provincial' => 'Submitted to Provincial',
        'reverted_by_provincial' => 'Reverted by Provincial',
        'forwarded_to_coordinator' => 'Forwarded to Coordinator',
        'reverted_by_coordinator' => 'Reverted by Coordinator',
        'approved_by_coordinator' => 'Approved by Coordinator',
        'rejected_by_coordinator' => 'Rejected by Coordinator',
        // General user acting as Coordinator
        'approved_by_general_as_coordinator' => 'Approved by General (as Coordinator)',
        'reverted_by_general_as_coordinator' => 'Reverted by General (as Coordinator)',
        // General user acting as Provincial
        'approved_by_general_as_provincial' => 'Approved by General (as Provincial)',
        'reverted_by_general_as_provincial' => 'Reverted by General (as Provincial)',
        // Granular revert statuses
        'reverted_to_executor' => 'Reverted to Executor',
        'reverted_to_applicant' => 'Reverted to Applicant',
        'reverted_to_provincial' => 'Reverted to Provincial',
        'reverted_to_coordinator' => 'Reverted to Coordinator',
    ];

    protected $fillable = [
        'report_id',
        'project_id',
        'generated_by_user_id',
        'quarter',
        'year',
        'period_from',
        'period_to',
        'project_title',
        'project_type',
        'place',
        'society_name',
        'commencement_month_year',
        'in_charge',
        'total_beneficiaries',
        'goal',
        'account_period_start',
        'account_period_end',
        'amount_sanctioned_overview',
        'amount_forwarded_overview',
        'amount_in_hand',
        'total_balance_forwarded',
        'status',
        'revert_reason',
        'generated_from',
        'generated_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'commencement_month_year' => 'date',
        'account_period_start' => 'date',
        'account_period_end' => 'date',
        'generated_at' => 'datetime',
        'generated_from' => 'array',
        'amount_sanctioned_overview' => 'decimal:2',
        'amount_forwarded_overview' => 'decimal:2',
        'amount_in_hand' => 'decimal:2',
        'total_balance_forwarded' => 'decimal:2',
        'quarter' => 'integer',
        'year' => 'integer',
        'total_beneficiaries' => 'integer',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function details()
    {
        return $this->hasMany(QuarterlyReportDetail::class, 'quarterly_report_id', 'id');
    }

    public function objectives()
    {
        return $this->hasMany(\App\Models\Reports\Aggregated\AggregatedReportObjective::class, 'report_id')
                    ->where('report_type', 'quarterly');
    }

    public function photos()
    {
        return $this->hasMany(\App\Models\Reports\Aggregated\AggregatedReportPhoto::class, 'report_id')
                    ->where('report_type', 'quarterly');
    }

    public function aiInsights()
    {
        return $this->hasOne(\App\Models\Reports\AI\AIReportInsight::class, 'report_id')
                    ->where('report_type', 'quarterly');
    }

    public function aiTitle()
    {
        return $this->hasOne(\App\Models\Reports\AI\AIReportTitle::class, 'report_id')
                    ->where('report_type', 'quarterly');
    }

    // Helper methods
    public function getStatusLabel()
    {
        return self::$statusLabels[$this->status] ?? 'Unknown Status';
    }

    public function getStatusBadgeClass()
    {
        $badgeClasses = [
            'draft' => 'bg-secondary',
            'submitted_to_provincial' => 'bg-info',
            'reverted_by_provincial' => 'bg-warning',
            'forwarded_to_coordinator' => 'bg-primary',
            'reverted_by_coordinator' => 'bg-warning',
            'approved_by_coordinator' => 'bg-success',
            'rejected_by_coordinator' => 'bg-danger',
            'approved_by_general_as_coordinator' => 'bg-success',
            'reverted_by_general_as_coordinator' => 'bg-warning',
            'approved_by_general_as_provincial' => 'bg-success',
            'reverted_by_general_as_provincial' => 'bg-warning',
            'reverted_to_executor' => 'bg-warning',
            'reverted_to_applicant' => 'bg-warning',
            'reverted_to_provincial' => 'bg-warning',
            'reverted_to_coordinator' => 'bg-warning',
        ];

        return $badgeClasses[$this->status] ?? 'bg-secondary';
    }

    public function getPeriodLabel()
    {
        $quarterNames = [1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'];
        return ($quarterNames[$this->quarter] ?? 'Q' . $this->quarter) . ' ' . $this->year;
    }

    public function isEditable()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REVERTED_BY_PROVINCIAL,
            self::STATUS_REVERTED_BY_COORDINATOR,
            self::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
            self::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            self::STATUS_REVERTED_TO_EXECUTOR,
            self::STATUS_REVERTED_TO_APPLICANT,
            self::STATUS_REVERTED_TO_PROVINCIAL,
            self::STATUS_REVERTED_TO_COORDINATOR,
        ]);
    }
}
