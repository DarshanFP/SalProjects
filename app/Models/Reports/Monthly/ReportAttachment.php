<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $attachment_id
 * @property string $report_id
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $description
 * @property string|null $public_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment wherePublicUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReportAttachment extends Model
{
    use HasFactory;

    protected $table = 'report_attachments';

    protected $fillable = [
        'report_id',
        'attachment_id',  // Add attachment_id to the fillable properties
        'file_name',
        'file_path',
        'description',
        'public_url'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->attachment_id = self::generateAttachmentId($model->report_id);
        });
    }

    public static function generateAttachmentId($reportId)
    {
        $latestAttachment = self::where('report_id', $reportId)->orderBy('attachment_id', 'desc')->first();

        if ($latestAttachment) {
            $lastNumber = (int)substr($latestAttachment->attachment_id, -2);
            $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '01';
        }

        return $reportId . '.' . $newNumber;
    }

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
}
