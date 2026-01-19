<?php

namespace App\Models;

use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'previous_status',
        'new_status',
        'changed_by_user_id',
        'changed_by_user_role',
        'changed_by_user_name',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the project that owns this status history
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /**
     * Get the user who changed the status
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Get status label for display
     */
    public function getPreviousStatusLabelAttribute(): string
    {
        return Project::$statusLabels[$this->previous_status] ?? $this->previous_status ?? 'N/A';
    }

    /**
     * Get status label for display
     */
    public function getNewStatusLabelAttribute(): string
    {
        return Project::$statusLabels[$this->new_status] ?? $this->new_status;
    }
}
