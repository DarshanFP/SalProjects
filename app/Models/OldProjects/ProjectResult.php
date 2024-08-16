<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

class ProjectResult extends Model
{
    protected $fillable = ['result_id', 'objective_id', 'result'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->result_id)) {
                $model->result_id = $model->generateResultId();
            }
        });
    }

    private function generateResultId()
    {
        $latestResult = self::where('objective_id', $this->objective_id)->latest('id')->first();
        $sequenceNumber = $latestResult ? intval(substr($latestResult->result_id, -2)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);

        return $this->objective_id . '-RES-' . $sequenceNumberPadded;
    }

    public function objective()
    {
        return $this->belongsTo(ProjectObjective::class, 'objective_id', 'objective_id');
    }

    
}
