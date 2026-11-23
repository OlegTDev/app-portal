<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use HasFactory, NodeTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'organization_code',
        'sort_order',
        'is_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Creator.
     */
    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Updater.
     */
    public function updater()
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }

    /**
     * Organization.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_code', 'code');
    }

    /**
     * Scope: enabled only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
