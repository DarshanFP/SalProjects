<?php

namespace App\Models\Reports\Monthly;

use App\Models\OldProjects\ProjectBudget;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class DPAccountDetail extends Model
{
    use HasFactory;

    protected $table = 'DP_AccountDetails';

    protected $fillable = [
        'project_id',
        'report_id',
        'particulars',
        'amount_forwarded',
        'amount_sanctioned',
        'total_amount',
        'expenses_last_month',
        'expenses_this_month',
        'total_expenses',
        'balance_amount',
    ];
    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
    public function projectBudget()
    {
        return $this->belongsTo(ProjectBudget::class, 'project_id', 'project_id');
    }


}
