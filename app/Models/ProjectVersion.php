<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'created_by',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectVersionFile::class)->orderBy('sort_order');
    }
}
