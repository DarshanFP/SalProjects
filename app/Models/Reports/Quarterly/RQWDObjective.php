<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQWDObjective extends Model
{
    use HasFactory;

    protected $table = 'rqwd_objectives';

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
        return $this->belongsTo(RQWDReport::class, 'report_id');
    }

    public function activities()
    {
        return $this->hasMany(RQWDActivity::class, 'objective_id');
    }
}
