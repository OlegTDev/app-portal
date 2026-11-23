<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;

class Department extends Model
{
    use HasFactory, NodeTrait;

    protected $fillable = [
        'organization_code',
        'department_index',
        'name',
        'short_name',
        'use_card',
        'sort_order',
    ];

    protected $casts = [
        'use_card' => 'boolean',
    ];

    /**
     * Organization this department belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_code', 'code');
    }

    /**
     * Employees in this department.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'department_employee', 'department_id', 'employee_id', 'id', 'id')
            ->withPivot(['position', 'rank', 'phone', 'responsibilities', 'sort_order', 'photo_path'])
            ->withTimestamps();
    }

    /**
     * Created items.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }
}
