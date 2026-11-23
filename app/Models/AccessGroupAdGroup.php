<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessGroupAdGroup extends Model
{
    use HasFactory;

    protected $table = 'access_group_ad_groups';

    protected $fillable = [
        'access_group_id',
        'group_dn',
        'group_name',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * Access group.
     */
    public function accessGroup(): BelongsTo
    {
        return $this->belongsTo(AccessGroup::class, 'access_group_id');
    }
}
