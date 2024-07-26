<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DPObjective extends Model
{
    use HasFactory;

    protected $table = 'DP_Objectives';
    protected $primaryKey = 'objective_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'objective_id',
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
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }

    public function activities()
    {
        return $this->hasMany(DPActivity::class, 'objective_id', 'objective_id');
    }
}
