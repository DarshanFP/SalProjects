<?php

namespace App\Models\Reports\Quarterly;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDPReport extends Model
{
    protected $table = 'rqdp_reports';  // Ensure the table name is correct

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
        'account_period_start',
        'account_period_end',
        'amount_sanctioned_overview',
        'amount_forwarded_overview',
        'total_balance_forwarded',
        'amount_in_hand',
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

