<?php

namespace App\Models;

use App\Authorization\RolePermissions;
use App\Enums\Civility;
use App\Enums\Permission;
use App\Enums\Role;
use App\Services\ProfilePhotoService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'first_name',
        'last_name',
        'civility',
        'email',
        'password',
        'role',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'profile_photo_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'civility' => Civility::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            app(ProfilePhotoService::class)->deleteStoredFile($user->profile_photo_path);
        });
    }

    public function displayName(): string
    {
        $composed = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $composed !== '' ? $composed : $this->name;
    }

    public function profilePhotoUrl(): ?string
    {
        if (! is_string($this->profile_photo_path) || $this->profile_photo_path === '') {
            return null;
        }

        return route('users.photo', $this);
    }

    public function canViewCompanyFinance(): bool
    {
        return $this->hasPermission(Permission::ViewReports)
            && $this->hasPermission(Permission::ManageExpenses);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function hasPermission(Permission $permission): bool
    {
        return RolePermissions::allows($this->role, $permission);
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    public function isBoss(): bool
    {
        return $this->role->isBoss();
    }

    /**
     * @return list<string>
     */
    public function permissionValues(): array
    {
        return RolePermissions::valuesFor($this->role);
    }
}
