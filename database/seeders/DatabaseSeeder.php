<?php

namespace Database\Seeders;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Mmii;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        Mmii::factory(148)->create();
        //users
        User::factory(10)->mmi1()->create();
        User::factory(20)->mmi2()->create();
        User::factory(20)->mmi3()->create();
        User::factory(5)->staff()->create();
        User::factory(5)->student()->create();

        for($i = 0; $i < 50; $i++) {
            CardTemplate::factory()->student()->withLevel(1)->withMmii(Mmii::inRandomOrder()->first())->create();
        }
        for($i = 0; $i < 52; $i++) {
            CardTemplate::factory()->student()->withLevel(2)->withMmii(Mmii::inRandomOrder()->first())->create();
        }
        for($i = 0; $i < 41; $i++) {
            CardTemplate::factory()->student()->withLevel(3)->withMmii(Mmii::inRandomOrder()->first())->create();
        }
        for($i = 0; $i < 15; $i++) {
            CardTemplate::factory()->staff()->withMmii(Mmii::inRandomOrder()->first())->create();
        }
        CardTemplate::factory(20)->object()->create();

        $templates = CardTemplate::all();

        foreach ($templates as $template) {

            $rarity = match($template->type) {
                CardTypes::STUDENT => match($template->level) {
                    1 => CardRarity::COMMON,
                    2 => CardRarity::UNCOMMON,
                    3 => CardRarity::RARE,
                    default => CardRarity::COMMON,
                },
                CardTypes::STAFF => CardRarity::RARE,
                CardTypes::OBJECT => CardRarity::COMMON,
                default => CardRarity::COMMON,
            };

            // Créer la version de base
            CardVersion::factory()
                ->forTemplate($template->id)  // Utiliser l'ID directement
                ->withRarity($rarity)
                ->create();

            // Version épique aléatoire
            if (in_array($template->type, ['student', 'staff']) && rand(1, 100) <= 2) {
                CardVersion::factory()
                    ->forTemplate($template->id)  // Utiliser l'ID directement
                    ->withRarity(CardRarity::EPIC)
                    ->create();
            }
        }

        //cartes épiques
        $epicTemplates = CardTemplate::where('level', 2)
            ->orWhere('level', 3)
            ->orWhere('type', CardTypes::STAFF)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($epicTemplates as $template) {
            CardVersion::factory()
                ->forTemplate($template->id)
                ->withRarity(CardRarity::EPIC)
                ->create();
        }
    }
}
