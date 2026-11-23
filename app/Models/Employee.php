<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'full_name',
        'position',
        'rank',
        'phone',
        'phone_additional',
        'office',
        'department_name',
        'organization_code',
        'photo_path',
        'ad_data',
        'is_active',
    ];

    protected $casts = [
        'ad_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Organizations this employee belongs to.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'employee_organization', 'employee_id', 'organization_code', 'id', 'code');
    }

    /**
     * Departments this employee belongs to.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_employee', 'employee_id', 'department_id', 'id', 'id');
    }

    /**
     * Portal user account.
     */
    public function portalUser(): HasOne
    {
        return $this->hasOne(PortalUser::class, 'employee_id', 'id');
    }

    /**
     * Created news/articles.
     */
    public function createdNews(): HasMany
    {
        return $this->hasMany(News::class, 'created_by');
    }

    /**
     * Created items across all models.
     */
    public function createdItems(): HasMany
    {
        return $this->hasMany(News::class, 'created_by')
            ->union($this->hasMany(Department::class, 'created_by'))
            ->union($this->hasMany(MenuItem::class, 'created_by'));
    }
}
