<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Center Model
 *
 * @property int $id
 * @property int $province_id
 * @property int|null $society_id
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Province $province
 * @property-read Society|null $society
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
class Center extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'province_id',
        'society_id',
        'name',
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
     * Get the province that owns this center.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the society relationship (nullable - centers belong to provinces, not societies).
     * This is kept for backward compatibility but centers are shared across all societies in a province.
     */
    public function society(): BelongsTo
    {
        return $this->belongsTo(Society::class);
    }

    /**
     * Get all societies that can access this center (all societies in the same province).
     */
    public function availableSocieties()
    {
        return Society::where('province_id', $this->province_id);
    }

    /**
     * Get all users in this center.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'center_id');
    }

    /**
     * Scope a query to only include active centers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter centers by province.
     */
    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    /**
     * Scope a query to filter centers by society.
     */
    public function scopeBySociety($query, $societyId)
    {
        return $query->where('society_id', $societyId);
    }

    /**
     * Scope a query to get centers by province name.
     */
    public function scopeByProvinceName($query, $provinceName)
    {
        return $query->whereHas('province', function ($q) use ($provinceName) {
            $q->where('name', $provinceName);
        });
    }
}
