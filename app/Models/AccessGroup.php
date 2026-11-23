<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_key',
        'title',
        'note',
        'created_by',
        'updated_by',
    ];

    /**
     * Direct employee members.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'access_group_employee', 'access_group_id', 'employee_id')
            ->withTimestamps()
            ->withPivot('expired_at');
    }

    /**
     * AD group mappings.
     */
    public function adGroups(): HasMany
    {
        return $this->hasMany(AccessGroupAdGroup::class, 'access_group_id');
    }

    /**
     * Creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Updater.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }
}
