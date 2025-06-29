<?php

namespace App\Models;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $R_comment_id
 * @property string $report_id
 * @property int $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read DPReport $report
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereRCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereUserId($value)
 * @mixin \Eloquent
 */
class ReportComment extends Model
{
    use HasFactory;

    protected $primaryKey = 'R_comment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'R_comment_id',
        'report_id',
        'user_id',
        'comment',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
