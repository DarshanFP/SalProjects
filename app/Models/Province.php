<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Province Model
 *
 * @property int $id
 * @property string $name
 * @property int|null $created_by
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Center> $centers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Society> $societies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $provincialUsers
 */
class Province extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'created_by',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this province.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all centers for this province.
     */
    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }

    /**
     * Get all active centers for this province.
     */
    public function activeCenters(): HasMany
    {
        return $this->hasMany(Center::class)->where('is_active', true);
    }

    /**
     * Get all societies for this province.
     */
    public function societies(): HasMany
    {
        return $this->hasMany(Society::class);
    }

    /**
     * Get all active societies for this province.
     */
    public function activeSocieties(): HasMany
    {
        return $this->hasMany(Society::class)->where('is_active', true);
    }

    /**
     * Get all users in this province.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'province_id');
    }

    /**
     * Get all provincial users for this province.
     *
     * This includes:
     * 1. Users with role='provincial' assigned via province_id (single province assignment)
     * 2. Users with role='general' assigned via pivot table (multiple province assignment)
     *
     * General users can manage multiple provinces, so they use the pivot table.
     * Provincial users typically manage one province, so they use province_id.
     */
    public function provincialUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'provincial_user_province', 'province_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get provincial users via province_id (for backward compatibility with role='provincial').
     * This is used for users who are assigned to a single province.
     */
    public function provincialUsersViaForeignKey(): HasMany
    {
        return $this->hasMany(User::class, 'province_id')
            ->where('role', 'provincial');
    }

    /**
     * Get all provincial users (both via pivot and via foreign key).
     * This combines both relationships to get all provincial users for this province.
     */
    public function getAllProvincialUsers()
    {
        // Get users via pivot table (mainly general users)
        $pivotUsers = $this->provincialUsers()->get();

        // Get users via province_id (provincial users)
        $foreignKeyUsers = $this->provincialUsersViaForeignKey()->get();

        // Merge and return unique users
        return $pivotUsers->merge($foreignKeyUsers)->unique('id');
    }

    /**
     * Scope a query to only include active provinces.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
