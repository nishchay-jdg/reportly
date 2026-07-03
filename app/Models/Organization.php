<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'notification_email',
        'notify_on_comment',
        'notify_on_first_view',
    ];

    protected function casts(): array
    {
        return [
            'notify_on_comment' => 'boolean',
            'notify_on_first_view' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function brandKit(): HasOne
    {
        return $this->hasOne(BrandKit::class);
    }
}
