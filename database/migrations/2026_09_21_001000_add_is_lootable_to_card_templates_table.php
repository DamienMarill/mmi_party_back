<?php

use App\Enums\CardTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('card_templates', function (Blueprint $table): void {
            $table->boolean('is_lootable')->default(true)->after('base_user');
        });

        DB::table('card_templates')
            ->where('type', CardTypes::STUDENT->value)
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'card_templates.base_user')
                    ->where('users.groupe', 'alumni');
            })
            ->update(['is_lootable' => false]);
    }

    public function down(): void
    {
        Schema::table('card_templates', function (Blueprint $table): void {
            $table->dropColumn('is_lootable');
        });
    }
};
