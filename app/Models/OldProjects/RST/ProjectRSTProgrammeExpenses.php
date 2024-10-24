<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectRSTProgrammeExpenses extends Model
{
    use HasFactory;

    protected $table = 'project_RST_programme_expenses';

    protected $fillable = [
        'programme_expense_id',
        'project_id',
        'particular',
        'year_1',
        'year_2',
        'year_3',
        'year_4'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->programme_expense_id = $model->generateProgrammeExpenseId();
        });
    }

    private function generateProgrammeExpenseId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->programme_expense_id, -4)) + 1 : 1;

        return 'RST-PE-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
