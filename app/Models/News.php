<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

class News extends Model
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasTags, LogsActivity;

    protected $fillable = [
        'title',
        'summary',
        'body',
        'category_id',
        'organization_code',
        'department_name',
        'published_at',
        'expired_at',
        'pinned_at',
        'is_enabled',
        'on_main_page',
        'likes_count',
        'comments_count',
        'visits_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'date',
        'expired_at' => 'date',
        'pinned_at' => 'datetime',
        'is_enabled' => 'boolean',
        'on_main_page' => 'boolean',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'visits_count' => 'integer',
    ];

    /**
     * Activity log options.
     */
    protected static string $logExcept = ['visits_count'];

    public function getActivitylogOptions(): \Spatie\ActivityLog\LogOptions
    {
        return \Spatie\ActivityLog\LogOptions::defaults()
            ->logOnly(['title', 'summary', 'body', 'is_enabled', 'on_main_page', 'published_at', 'expired_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_code', 'code');
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

    /**
     * Likes (polymorphic).
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }

    /**
     * Comments (polymorphic).
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /*
     * Scopes
     */

    public function scopePublished($query): void
    {
        $query->where('is_enabled', true)
            ->where('published_at', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('expired_at')->orWhere('expired_at', '>=', now()->toDateString()));
    }

    public function scopeByOrganization($query, string $code): void
    {
        $query->where('organization_code', $code);
    }

    public function scopeByCategory($query, string $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }

    public function scopeOnMainPage($query): void
    {
        $query->where('on_main_page', true);
    }

    public function scopeNotExpired($query): void
    {
        $query->where(fn ($q) => $q->whereNull('expired_at')->orWhere('expired_at', '>=', now()->toDateString()));
    }

    public function scopePinned($query): void
    {
        $query->whereNotNull('pinned_at');
    }

    public function scopeSearch($query, string $q): void
    {
        $query->whereRaw("search_vector @@ plainto_tsquery('russian', ?)", [$q])
            ->orderByRaw("ts_rank(search_vector, plainto_tsquery('russian', ?)) DESC", [$q]);
    }
}
