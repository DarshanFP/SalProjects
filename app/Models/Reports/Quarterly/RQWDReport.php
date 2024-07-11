<?php

namespace App\Models\Reports\Quarterly;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQWDReport extends Model
{
    use HasFactory;

    protected $table = 'rqwd_reports';

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
        'prjct_amount_sanctioned',
        'l_y_amount_forwarded',
        'amount_in_hand',
        'total_balance_forwarded',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inmatesProfiles()
    {
        return $this->hasMany(RQWDInmatesProfile::class, 'report_id');
    }

    public function objectives()
    {
        return $this->hasMany(RQWDObjective::class, 'report_id');
    }

    public function outlooks()
    {
        return $this->hasMany(RQWDOutlook::class, 'report_id');
    }

    public function photos()
    {
        return $this->hasMany(RQWDPhoto::class, 'report_id');
    }

    public function accountDetails()
    {
        return $this->hasMany(RQWDAccountDetail::class, 'report_id');
    }
}
