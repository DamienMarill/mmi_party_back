<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Enums\UserGroups;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements FilamentUser, JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'um_email',
        'password',
        'github_id',
        'groupe',
        'moodle_id',
        'moodle_username',
        'email_verified_at',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'groupe' => UserGroups::class,
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $user): void {
            if ($user->wasChanged('groupe')) {
                $user->syncOwnedCardRaritiesWithGroupe();
                $user->syncOwnedCardLootabilityWithGroupe();
            }
        });
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'um_email' => 'required|email|unique:users|ends_with:umontpellier.fr',
            'password' => 'string|min:8|uppercase|lowercase|number|special|uncompromised',
            'github_id' => 'nullable|string',
            'groupe' => 'required|enum:'.UserGroups::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function collection()
    {
        return $this->hasMany(CardInstance::class)
            ->with(['cardVersion', 'cardVersion.cardTemplate', 'cardVersion.cardTemplate.mmii']);
    }

    public function mmii()
    {
        return $this->belongsTo(Mmii::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function routeNotificationForMail(): string
    {
        return $this->um_email;
    }

    public function syncOwnedCardRaritiesWithGroupe(): void
    {
        $targetRarity = $this->promoRarity();

        if (! $targetRarity) {
            return;
        }

        CardVersion::query()
            ->whereIn('rarity', self::promotableCardRarities())
            ->whereHas('cardTemplate', function ($query): void {
                $query->where('base_user', $this->id)
                    ->where('type', CardTypes::STUDENT);
            })
            ->update(['rarity' => $targetRarity->value]);
    }

    /**
     * Une carte étudiant n'est lootable que tant que son propriétaire n'est pas alumni.
     * Aligne les changements de groupe manuels (édition admin unitaire) sur ce que fait
     * SchoolYearTransitionService lors de la transition annuelle : dès qu'un étudiant
     * devient alumni, ses cartes cessent de tomber dans les boosters (et redeviennent
     * lootables s'il repasse dans une promo, en cas de correction).
     */
    public function syncOwnedCardLootabilityWithGroupe(): void
    {
        CardTemplate::query()
            ->where('base_user', $this->id)
            ->where('type', CardTypes::STUDENT)
            ->update(['is_lootable' => $this->groupe !== UserGroups::ALUMNI]);
    }

    public function promoRarity(): ?CardRarity
    {
        return match ($this->groupe) {
            UserGroups::MMI1 => CardRarity::COMMON,
            UserGroups::MMI2 => CardRarity::UNCOMMON,
            UserGroups::MMI3 => CardRarity::RARE,
            default => null,
        };
    }

    public static function promotableCardRarities(): array
    {
        return [
            CardRarity::COMMON->value,
            CardRarity::UNCOMMON->value,
            CardRarity::RARE->value,
        ];
    }

    // Hub relations
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(HubInvitation::class, 'sender_id');
    }

    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(HubInvitation::class, 'receiver_id');
    }

    public function roomsAsPlayerOne(): HasMany
    {
        return $this->hasMany(HubRoom::class, 'player_one_id');
    }

    public function roomsAsPlayerTwo(): HasMany
    {
        return $this->hasMany(HubRoom::class, 'player_two_id');
    }

    public function promoUnlocks(): HasMany
    {
        return $this->hasMany(PromoUnlock::class);
    }
}
