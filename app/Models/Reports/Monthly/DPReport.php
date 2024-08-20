<?php

namespace App\Models\Reports\Monthly;

use App\Models\ReportComment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DPReport extends Model
{
    use HasFactory;

    protected $table = 'DP_Reports';
    protected $primaryKey = 'report_id';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'status'

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

    public function generateCommentId()
    {
        $latestComment = $this->comments()->orderBy('created_at', 'desc')->first();
        $nextNumber = $latestComment ? (int)substr($latestComment->R_comment_id, -3) + 1 : 1;
        return $this->report_id . '.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
