<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
