<?php

namespace App\Models\Reports\Monthly;

use App\Models\ReportComment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property string $report_id
 * @property string $project_id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $project_type
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $report_month_year
 * @property string|null $report_before_id
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $amount_sanctioned_overview
 * @property string|null $amount_forwarded_overview
 * @property string|null $amount_in_hand
 * @property string|null $total_balance_forwarded
 * @property string $status
 * @property string|null $revert_reason
 * @property string|null $pmc_comments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPAccountDetail> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\QRDLAnnexure> $annexures
 * @property-read int|null $annexures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\ReportAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReportComment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\OldProjects\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\RQISAgeProfile> $rqis_age_profile
 * @property-read int|null $rqis_age_profile_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\RQSTTraineeProfile> $rqst_trainee_profile
 * @property-read int|null $rqst_trainee_profile_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\RQWDInmatesProfile> $rqwd_inmate_profile
 * @property-read int|null $rqwd_inmate_profile_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAmountForwardedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAmountSanctionedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereProjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereReportBeforeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereReportMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereRevertReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereUserId($value)
 * @mixin \Eloquent
 */
class DPReport extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\DPReportFactory::new();
    }

    protected $table = 'DP_Reports';
    protected $primaryKey = 'report_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Status constants
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
        'draft' => 'Draft (Executor still working)',
        'submitted_to_provincial' => 'Executor submitted to Provincial',
        'reverted_by_provincial' => 'Returned by Provincial for changes',
        'forwarded_to_coordinator' => 'Provincial sent to Coordinator',
        'reverted_by_coordinator' => 'Coordinator sent back for changes',
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
        'user_id',
        'project_id',
        'project_title',
        'project_type',
        'place',
        'society_name',
        'commencement_month_year',
        'in_charge',
        'total_beneficiaries',
        'report_month_year',
        'report_before_id',
        'goal',
        'account_period_start',
        'account_period_end',
        'amount_sanctioned_overview',
        'amount_forwarded_overview',
        'amount_in_hand',
        'total_balance_forwarded',
        'status',
        'revert_reason',
        'pmc_comments'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\OldProjects\Project::class);
    }

    public function objectives()
    {
        return $this->hasMany(DPObjective::class, 'report_id', 'report_id');
    }

    public function accountDetails()
    {
        return $this->hasMany(DPAccountDetail::class, 'report_id', 'report_id');
    }

    public function photos()
    {
        return $this->hasMany(DPPhoto::class, 'report_id', 'report_id');
    }

    public function outlooks()
    {
        return $this->hasMany(DPOutlook::class, 'report_id', 'report_id');
    }

    public function annexures()
    {
        return $this->hasMany(QRDLAnnexure::class, 'report_id', 'report_id');
    }
    public function rqis_age_profile()
    {
        return $this->hasMany(RQISAgeProfile::class, 'report_id', 'report_id');
    }
    public function rqst_trainee_profile()
    {
        return $this->hasMany(RQSTTraineeProfile::class, 'report_id', 'report_id');
    }
    public function rqwd_inmate_profile()
    {
        return $this->hasMany(RQWDInmatesProfile::class, 'report_id', 'report_id');
    }
    public function comments()
    {
        return $this->hasMany(ReportComment::class, 'report_id', 'report_id');
    }

    public function activityHistory()
    {
        return $this->hasMany(\App\Models\ActivityHistory::class, 'related_id', 'report_id')
            ->where('type', 'report')
            ->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->hasMany(ReportAttachment::class, 'report_id', 'report_id');
    }

    public function aiValidation()
    {
        return $this->hasOne(\App\Models\Reports\AI\AIReportValidationResult::class, 'report_id', 'report_id')
                    ->where('report_type', 'monthly');
    }

    public function generateCommentId()
    {
        $latestComment = $this->comments()->orderBy('created_at', 'desc')->first();
        $nextNumber = $latestComment ? (int)substr($latestComment->R_comment_id, -3) + 1 : 1;
        return $this->report_id . '.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusLabel()
    {
        return self::$statusLabels[$this->status] ?? 'Unknown Status';
    }

    public function getStatusBadgeClass()
    {
        $badgeClasses = [
            'draft' => 'bg-secondary',
            'submitted_to_provincial' => 'bg-primary',
            'reverted_by_provincial' => 'bg-warning',
            'forwarded_to_coordinator' => 'bg-info',
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

    /**
     * Check if report is approved by coordinator or general as coordinator
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED_BY_COORDINATOR,
            self::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
            self::STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL,
        ]);
    }

    /**
     * Check if report is editable
     */
    public function isEditable(): bool
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

    /**
     * Check if report is submitted to provincial
     */
    public function isSubmittedToProvincial(): bool
    {
        return $this->status === self::STATUS_SUBMITTED_TO_PROVINCIAL;
    }

    /**
     * Check if report is forwarded to coordinator
     */
    public function isForwardedToCoordinator(): bool
    {
        return $this->status === self::STATUS_FORWARDED_TO_COORDINATOR;
    }

    /**
     * Get approved expenses (only if report is approved)
     */
    public function getApprovedExpenses(): float
    {
        if (!$this->isApproved()) {
            return 0.0;
        }

        if (!$this->relationLoaded('accountDetails')) {
            $this->load('accountDetails');
        }

        return $this->accountDetails->sum('total_expenses') ?? 0.0;
    }

    /**
     * Get unapproved expenses (only if report is not approved)
     */
    public function getUnapprovedExpenses(): float
    {
        if ($this->isApproved()) {
            return 0.0;
        }

        if (!$this->relationLoaded('accountDetails')) {
            $this->load('accountDetails');
        }

        return $this->accountDetails->sum('total_expenses') ?? 0.0;
    }
}
