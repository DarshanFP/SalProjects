<?php

namespace App\Models\Reports\Quarterly;

use App\Models\Reports\Quarterly\RQSTAccountDetails;
use App\Models\Reports\Quarterly\RQSTObjective;
use App\Models\Reports\Quarterly\RQSTOutlook;
use App\Models\Reports\Quarterly\RQSTPhoto;
use App\Models\Reports\Quarterly\RQSTTraineeProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQSTReport extends Model
{
    use HasFactory;

    protected $table = 'rqst_reports';

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
        return $this->hasMany(RQSTTraineeProfile::class, 'report_id');
    }

    public function objectives()
    {
        return $this->hasMany(RQSTObjective::class, 'report_id');
    }

    public function accountDetails()
    {
        return $this->hasMany(RQSTAccountDetails::class, 'report_id');
    }

    public function outlooks()
    {
        return $this->hasMany(RQSTOutlook::class, 'report_id');
    }

    public function photos()
    {
        return $this->hasMany(RQSTPhoto::class, 'report_id');
    }
}
