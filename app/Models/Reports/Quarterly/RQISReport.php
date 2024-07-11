<?php

namespace App\Models\Reports\Quarterly;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQISReport extends Model
{
    use HasFactory;

    protected $table = 'rqis_reports';

    protected $fillable = [
        'user_id',
        'project_title',
        'place',
        'society_name',
        'commencement_month_year',
        'province',
        'in_charge',
        'total_beneficiaries',
        'institution_type',
        'beneficiary_statistics',
        'monitoring_period',
        'goal',
        'account_period_start',
        'account_period_end',
        'amount_sanctioned_overview',
        'amount_forwarded_overview',
        'total_balance_forwarded',
        'amount_in_hand', // New fillable field

        // Age profile totals
        'total_up_to_previous_below_5',
        'total_present_academic_below_5',
        'total_up_to_previous_6_10',
        'total_present_academic_6_10',
        'total_up_to_previous_11_15',
        'total_present_academic_11_15',
        'total_up_to_previous_16_above',
        'total_present_academic_16_above',
        'grand_total_up_to_previous',
        'grand_total_present_academic',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function objectives()
    {
        return $this->hasMany(RQISObjective::class, 'report_id');
    }

    public function outlooks()
    {
        return $this->hasMany(RQISOutlook::class, 'report_id');
    }

    public function photos()
    {
        return $this->hasMany(RQISPhoto::class, 'report_id');
    }

    public function accountDetails()
    {
        return $this->hasMany(RQISAccountDetail::class, 'report_id');
    }

    public function ageProfiles()
    {
        return $this->hasMany(RQISAgeProfile::class, 'report_id');
    }
}
