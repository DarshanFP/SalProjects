<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIIESScopeFinancialSupport extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_scope_financial_support';

    protected $fillable = [
        'IIES_financial_support_id',
        'project_id',
        'govt_eligible_scholarship',
        'scholarship_amt',
        'other_eligible_scholarship',
        'other_scholarship_amt',
        'family_contrib',
        'no_contrib_reason'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IIES_financial_support_id = $model->generateIIESFinancialSupportId();
        });
    }

    private function generateIIESFinancialSupportId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_financial_support_id, -4)) + 1 : 1;
        return 'IIES-FS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
