<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $photo_id
 * @property string $report_id
 * @property string|null $photo_path
 * @property string|null $photo_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto wherePhotoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto wherePhotoName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DPPhoto extends Model
{
    use HasFactory;

    protected $table = 'DP_Photos';
    protected $primaryKey = 'photo_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'photo_id',
        'report_id',
        'photo_path',
        'photo_name',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
}
