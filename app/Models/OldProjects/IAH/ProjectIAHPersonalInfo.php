<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IAH_info_id
 * @property string|null $project_id
 * @property string|null $name
 * @property int|null $age
 * @property string|null $gender
 * @property string|null $dob
 * @property string|null $aadhar
 * @property string|null $contact
 * @property string|null $address
 * @property string|null $email
 * @property string|null $guardian_name
 * @property int|null $children
 * @property string|null $caste
 * @property string|null $religion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereAadhar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereChildren($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereGuardianName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereIAHInfoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIAHPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_personal_info';

    protected $fillable = [
        'IAH_info_id',
        'project_id',
        'name',
        'age',
        'gender',
        'dob',
        'aadhar',
        'contact',
        'address',
        'email',
        'guardian_name',
        'children',
        'caste',
        'religion',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_info_id = $model->generateIAHInfoId();
        });
    }

    private function generateIAHInfoId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_info_id, -4)) + 1 : 1;
        return 'IAH-INFO-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
