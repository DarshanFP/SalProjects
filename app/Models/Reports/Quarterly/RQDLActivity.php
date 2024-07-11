<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDLActivity extends Model
{
    use HasFactory;

    protected $table = 'rqdl_activities';

    protected $fillable = [
        'objective_id',
        'month',
        'summary_activities',
        'qualitative_quantitative_data',
        'intermediate_outcomes',
    ];

    public function objective()
    {
        return $this->belongsTo(RQDLObjective::class, 'objective_id');
    }
}
