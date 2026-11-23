<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class MenuItem extends Model
{
    use HasFactory, NodeTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'link',
        'icon',
        'target',
        'position',
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
     * Scope: main menu items.
     */
    public function scopeMain($query)
    {
        return $query->where('position', 'main');
    }

    /**
     * Scope: left menu items.
     */
    public function scopeLeft($query)
    {
        return $query->where('position', 'left');
    }

    /**
     * Scope: enabled only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
