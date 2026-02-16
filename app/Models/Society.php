<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Society Model
 *
 * @property int $id
 * @property int $province_id
 * @property string $name
 * @property string|null $address
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Province $province
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Center> $centers
 */
class Society extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'province_id',
        'name',
        'address',
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
     * Get the province that owns this society.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get all centers available to this society.
     * Centers belong to provinces, so all centers in the society's province are available.
     */
    public function centers()
    {
        return Center::where('province_id', $this->province_id);
    }

    /**
     * Get all active centers available to this society.
     */
    public function activeCenters()
    {
        return Center::where('province_id', $this->province_id)
            ->where('is_active', true);
    }

    /**
     * Scope a query to only include active societies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter societies by province.
     */
    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }
}
