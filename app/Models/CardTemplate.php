<?php

namespace App\Models;

use App\Casts\CardStatsCast;
use App\Services\ShapeValidator;
use App\Services\StatsValidator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'type' => CardTypes::class,
        'level' => 'integer',
        'stats' => 'array',
        'shape' => 'array',
    ];

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

    public function cardVersions()
    {
        return $this->hasMany(CardVersion::class);
    }
}
