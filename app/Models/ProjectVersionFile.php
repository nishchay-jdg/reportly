<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectVersionFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_version_id',
        'filename',
        'type',
        'content',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'project_version_id' => 'integer',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class, 'project_version_id');
    }
}
