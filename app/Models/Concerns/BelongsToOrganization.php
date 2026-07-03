<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            if ($user->is_platform_admin) {
                return;
            }

            $builder->where($builder->qualifyColumn('organization_id'), $user->organization_id);
        });

        static::creating(function (Model $model) {
            if ($model->organization_id === null && auth()->check()) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });
    }
}
