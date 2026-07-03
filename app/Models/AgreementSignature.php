<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementSignature extends Model
{
    protected $fillable = [
        'share_id',
        'full_name',
        'email',
        'company_name',
        'signature_text',
        'terms_url',
        'ip_address',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }
}
