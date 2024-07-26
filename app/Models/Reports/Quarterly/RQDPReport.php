<?php

namespace App\Models\Reports\Quarterly;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDPReport extends Model
{
    use HasFactory;

    protected $table = 'rqdp_reports';

    protected $fillable = [
        'user_id',
        'project_id',
        'project_title',
        'place',
        'society_name',
        'commencement_month_year',
        'in_charge',
        'total_beneficiaries',
        'reporting_period_from',
        'reporting_period_to',
        'goal',
        'account_period_start',
        'account_period_end',
        'amount_sanctioned_overview',
        'amount_forwarded_overview',
        'amount_in_hand',
        'total_balance_forwarded',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function objectives()
    {
        return $this->hasMany(RQDPObjective::class, 'report_id');
    }

    public function outlooks()
    {
        return $this->hasMany(RQDPOutlook::class, 'report_id');
    }

    public function photos()
    {
        return $this->hasMany(RQDPPhoto::class, 'report_id');
    }

    public function accountDetails()
    {
        return $this->hasMany(RQDPAccountDetail::class, 'report_id');
    }
}
