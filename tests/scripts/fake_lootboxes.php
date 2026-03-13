<?php
/**
 * Script de test : génère de fausses ouvertures de boosters pour les joueurs existants.
 * Usage : php artisan tinker --execute="require base_path('tests/scripts/fake_lootboxes.php');"
 * OU directement : php artisan tinker < tests/scripts/fake_lootboxes.php
 */

use App\Enums\LootboxTypes;
use App\Models\CardInstance;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\User;
use Illuminate\Support\Str;

$users = User::all();
$cardVersions = CardVersion::all();

if ($users->isEmpty()) {
    echo "❌ Aucun utilisateur trouvé.\n";
    return;
}

if ($cardVersions->isEmpty()) {
    echo "❌ Aucune CardVersion trouvée.\n";
    return;
}

echo "👾 Génération de faux boosters pour {$users->count()} joueurs...\n";

$now = now();
$created = 0;

foreach ($users as $user) {
    // ≈ 2 à 6 boosters par joueur, répartis sur les 7 derniers jours
    $boosterCount = rand(2, 6);

    for ($b = 0; $b < $boosterCount; $b++) {
        $dayOffset = rand(0, 6);
        $lootbox = Lootbox::create([
            'id'           => Str::uuid(),
            'user_id'      => $user->id,
            'type'         => LootboxTypes::QUOTIDIAN->value,
            'slot_used_at' => $now->copy()->subDays($dayOffset),
            'created_at'   => $now->copy()->subDays($dayOffset)->addHours(rand(0, 23)),
            'updated_at'   => $now->copy()->subDays($dayOffset),
        ]);

        // 5 cartes par booster (avec des card_versions variées et distinctes)
        $pickedVersions = $cardVersions->random(min(5, $cardVersions->count()));
        foreach ($pickedVersions as $version) {
            CardInstance::create([
                'id'              => Str::uuid(),
                'card_version_id' => $version->id,
                'lootbox_id'      => $lootbox->id,
                'user_id'         => $user->id,
            ]);
        }
        $created++;
    }
    echo "  ✅ {$user->name} : {$boosterCount} boosters\n";
}

echo "\n🎉 Done ! {$created} boosters créés.\n";
