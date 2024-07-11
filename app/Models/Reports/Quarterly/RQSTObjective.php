<?php

namespace App\Models\Reports\Quarterly;

use App\Models\Reports\Quarterly\RQSTReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQSTObjective extends Model
{
    use HasFactory;

    protected $table = 'rqst_objectives';

    protected $fillable = [
        'report_id',
        'expected_outcome',
        'not_happened',
        'why_not_happened',
        'changes',
        'why_changes',
        'lessons_learnt',
        'todo_lessons_learnt',
    ];

    public function report()
    {
        return $this->belongsTo(RQSTReport::class, 'report_id');
    }

    public function activities()
    {
        return $this->hasMany(RqstActivity::class, 'objective_id');
    }
}
