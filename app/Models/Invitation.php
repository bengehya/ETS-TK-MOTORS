<?php

namespace App\Models;

use App\Enums\Civility;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'invited_by',
        'first_name',
        'last_name',
        'email',
        'civility',
        'role',
        'token_hash',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'civility' => Civility::class,
            'role' => Role::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAcceptable(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->role !== Role::BossPrincipal;
    }

    public function status(): string
    {
        if ($this->accepted_at !== null) {
            return 'accepted';
        }

        if ($this->revoked_at !== null) {
            return 'revoked';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'pending';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'accepted' => 'Acceptée',
            'revoked' => 'Révoquée',
            'expired' => 'Expirée',
            default => 'En attente',
        };
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
