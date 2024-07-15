<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDPObjective extends Model
{
    use HasFactory;

    protected $table = 'rqdp_objectives';

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
        return $this->belongsTo(RQDPReport::class, 'report_id');
    }

    public function activities()
    {
        return $this->hasMany(RQDPActivity::class, 'objective_id');
    }
}

