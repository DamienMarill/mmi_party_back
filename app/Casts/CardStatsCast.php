<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CardStatsCast implements CastsAttributes
{
    public function __construct(
        public int $dev = 0,
        public int $uxUi = 0,
        public int $graphisme = 0,
        public int $audiovisuel = 0,
        public int $troisD = 0,
        public int $communication = 0
    ) {}

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return new self(
            dev: $value['dev'] ?? 0,
            uxUi: $value['uxUi'] ?? 0,
            graphisme: $value['graphisme'] ?? 0,
            audiovisuel: $value['audiovisuel'] ?? 0,
            troisD: $value['troisD'] ?? 0,
            communication: $value['communication'] ?? 0
        );
    }
}
