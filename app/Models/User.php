<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\BelongsToSchool;
use App\Support\SchoolOrderAccess;
use Illuminate\Notifications\Notifiable;
use App\Models\Permission;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use BelongsToSchool;
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'utilisateurs';
    protected $primaryKey = 'idUtilisateur';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomPrenom',
        'email',
        'pwd',
        'fonction',
        'telephone',
        'genre',
        'droit',
        'idEcole',
        'managed_orders',
        'id_academie',
        'id_cap',
        'id_enseignant',
        'id_parent',
        'id_revendeur',
        'id_role',
        'image',
        'statut',
        'theme_preference',
        'locale_preference',
        'last_login_at',
        'last_activity',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pwd',
        'remember_token',
    ];

    protected ?array $permissionCanonicalCache = null;

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->pwd;
    }

    /**
     * Relative public path to the user's photo, falling back to the default
     * avatar when none was uploaded or the stored file is missing on disk.
     */
    public function getPhotoPathAttribute(): string
    {
        $default = 'assets/images/avatars/avatar-1.png';

        if ($this->image && file_exists(public_path($this->image))) {
            return $this->image;
        }

        return $default;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'pwd' => 'hashed',
            'managed_orders' => 'array',
            'last_login_at' => 'datetime',
            'last_activity' => 'datetime',
        ];
    }

    /**
     * Check if user has a specific permission.
     */
    public function userHasPermission($permissionName)
    {
        if ($this->droit === 'SupAdmin') {
            return true;
        }

        $canonical = Permission::canonicalName($permissionName);

        return in_array($canonical, $this->permissionCanonicalNames(), true);
    }

    public function userHasAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->userHasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    public function managedOrderLabels(): array
    {
        $orders = SchoolOrderAccess::normalizeMany($this->managed_orders ?? []);

        return collect($orders)
            ->mapWithKeys(fn ($order) => [$order => SchoolOrderAccess::ORDERS[$order]])
            ->all();
    }

    public function permissionCanonicalNames(): array
    {
        if ($this->permissionCanonicalCache !== null) {
            return $this->permissionCanonicalCache;
        }

        $permissions = $this->relationLoaded('permissions')
            ? $this->permissions
            : $this->permissions()->get(['permissions.name']);

        $this->permissionCanonicalCache = $permissions
            ->pluck('name')
            ->map(fn ($name) => Permission::canonicalName($name))
            ->unique()
            ->values()
            ->all();

        return $this->permissionCanonicalCache;
    }

    /**
     * Relationship with permissions via user_permission table.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission', 'user_id', 'permission_id');
    }

    /**
     * Relationship with Ecole.
     */
    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }

    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie', 'id_academie');
    }

    public function cap()
    {
        return $this->belongsTo(Cap::class, 'id_cap', 'id_cap');
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'id_parent', 'id_parent');
    }

    public function revendeur()
    {
        return $this->belongsTo(Revendeur::class, 'id_revendeur');
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class, 'user_id', 'idUtilisateur');
    }
}
