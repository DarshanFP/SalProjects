<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQISObjective extends Model
{
    use HasFactory;

    protected $table = 'rqis_objectives';

    protected $fillable = [
        'report_id',
        'objective',
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
        return $this->belongsTo(RQISReport::class, 'report_id');
    }

    public function activities()
    {
        return $this->hasMany(RQISActivity::class, 'objective_id');
    }
}
