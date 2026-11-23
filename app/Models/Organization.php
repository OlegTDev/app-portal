<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'parent_code',
        'sort_order',
        'description',
        'upload_images_path',
        'ended_at',
    ];

    /**
     * Parent organization.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_code', 'code');
    }

    /**
     * Child organizations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_code', 'code');
    }

    /**
     * Employees belonging to this organization.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_organization', 'organization_code', 'employee_id', 'code', 'id');
    }

    /**
     * Departments in this organization.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'organization_code', 'code');
    }

    /**
     * News published in this organization.
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'organization_code', 'code');
    }
}
