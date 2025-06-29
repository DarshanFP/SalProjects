<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $dla_beneficiary_name
 * @property string|null $dla_support_date
 * @property string|null $dla_self_employment
 * @property string|null $dla_amount_sanctioned
 * @property string|null $dla_monthly_profit
 * @property string|null $dla_annual_profit
 * @property string|null $dla_impact
 * @property string|null $dla_challenges
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure query()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaAnnualProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaMonthlyProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaSelfEmployment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaSupportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class QRDLAnnexure extends Model
{
    use HasFactory;

    protected $table = 'qrdl_annexure';

    protected $fillable = [
        'report_id',
        'dla_beneficiary_name',
        'dla_support_date',
        'dla_self_employment',
        'dla_amount_sanctioned',
        'dla_monthly_profit',
        'dla_annual_profit',
        'dla_impact',
        'dla_challenges',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id');
    }
}
