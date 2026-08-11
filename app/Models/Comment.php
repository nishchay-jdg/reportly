<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'share_id',
        'parent_id',
        'author_type',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_token',
        'body',
        'position_x',
        'position_y',
        'report_context',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'share_id' => 'integer',
            'parent_id' => 'integer',
            'user_id' => 'integer',
            'is_resolved' => 'boolean',
            'position_x' => 'float',
            'position_y' => 'float',
        ];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorName(): string
    {
        return $this->author_type === 'guest'
            ? ($this->guest_name ?? 'Guest')
            : ($this->user?->name ?? 'Team member');
    }

    public function isOwnedByGuestToken(string $token): bool
    {
        return $this->author_type === 'guest'
            && $this->guest_token
            && $token !== ''
            && hash_equals($this->guest_token, $token);
    }
}
