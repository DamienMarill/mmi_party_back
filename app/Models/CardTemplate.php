<?php

namespace App\Models;

use App\Casts\CardStatsCast;
use App\Services\ShapeValidator;
use App\Services\StatsValidator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\CardTypes;
use Illuminate\Validation\Rule;

class CardTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'level',
        'stats',
        'shape',
        'mmii_id',
        'base_user',
        'is_lootable',
    ];

    protected $casts = [
        'type' => CardTypes::class,
        'level' => 'integer',
        'stats' => 'array',
        'shape' => 'array',
        'is_lootable' => 'boolean',
    ];

    protected $appends = ['owner_promo'];

    // Charge en batch la promo du propriétaire (id + groupe uniquement) pour éviter le
    // N+1 sur les listes de cartes ; la relation elle-même est masquée du JSON.
    protected $with = ['baseUserPromo'];

    protected $hidden = ['baseUserPromo'];

    /**
     * Promo réelle du propriétaire de la carte (mmi1, mmi2, mmi3, alumni…), ou null pour
     * une carte fictive non encore attribuée. Le front s'en sert pour afficher "Alumni"
     * là où le level, plafonné à 3, ne distingue plus un MMI3 d'un alumni.
     * Reste correct même sans eager-load (lazy-load de secours), $with n'étant qu'une
     * optimisation.
     */
    public function getOwnerPromoAttribute(): ?string
    {
        return $this->baseUserPromo?->groupe?->value;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'type' => ['required', Rule::enum(CardTypes::class)],
            'level' => [
                'required_if:type,'.CardTypes::STUDENT->value,
                'nullable',
                'integer',
                'min:1',
                'max:3',
                // S'assurer que level est null si ce n'est pas un étudiant
                function ($attribute, $value, $fail) {
                    $type = request('type');
                    if ($type !== CardTypes::STUDENT->value && $value !== null) {
                        $fail("Le niveau doit être null pour les cartes non étudiantes.");
                    }
                }
            ],
            'stats' => [
                'required_if:type,'.CardTypes::STUDENT->value,
                'nullable',
                function($attribute, $value, $fail) {
                    if (request('type') === CardTypes::STUDENT->value) {
                        $level = request('level');
                        if (!StatsValidator::isValid($value, $level)) {
                            $fail("Les stats ne sont pas valides pour un MMI$level");
                        }
                    }
                }
            ],
            'shape' => [
                'required_if:type,'.CardTypes::STUDENT->value,
                function ($attribute, $value, $fail) {
                    $type = request('type');
                    if ($type !== CardTypes::STUDENT->value && $value !== null) {
                        $level = request('level');
                        if (!ShapeValidator::isValid($value, $level)) {
                            $fail("La forme n'est pas valide pour un MMI$level");
                        }
                    }
                }
            ],
            'mmii_id' => ['nullable', 'exists:mmiis,id'],
            'base_user' => ['nullable', 'exists:users,id'],
            'is_lootable' => ['required', 'boolean'],
        ];
    }

    public function mmii()
    {
        return $this->belongsTo(Mmii::class);
    }

    public function baseUser()
    {
        return $this->belongsTo(User::class, 'base_user');
    }

    /**
     * Variante de baseUser limitée à (id, groupe), dédiée à l'accessor owner_promo et
     * au eager-load automatique ($with) : on ne charge jamais le user complet dans le JSON.
     */
    public function baseUserPromo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'base_user')->select(['id', 'groupe']);
    }

    public function cardVersions()
    {
        return $this->hasMany(CardVersion::class);
    }
}
