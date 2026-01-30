<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserGroups;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

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
        ];
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'um_email' => 'required|email|unique:users|ends_with:umontpellier.fr',
            'password' => 'string|min:8|uppercase|lowercase|number|special|uncompromised',
            'github_id' => 'nullable|string',
            'groupe' => 'required|enum:' . UserGroups::class,
        ];
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
}
