<?php

namespace Database\Seeders;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Mmii;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 200; $i++) {
            Mmii::factory()->create();
        }

        $mmiis = Mmii::inRandomOrder()->get();
        $mmiiCounte = 0;

        for ($i = 0; $i < 50; $i++) {
            CardTemplate::factory()
                            ->student()
                            ->withLevel(1)
                            ->withMmii($mmiis[$mmiiCounte])
                            ->create();
            $mmiiCounte++;
        }

        for ($i = 0; $i < 52; $i++) {
            CardTemplate::factory()
                            ->student()
                            ->withLevel(2)
                            ->withMmii($mmiis[$mmiiCounte])
                            ->create();
            $mmiiCounte++;
        }

        for ($i = 0; $i < 41; $i++) {
            CardTemplate::factory()
                            ->student()
                            ->withLevel(3)
                            ->withMmii($mmiis[$mmiiCounte])
                            ->create();
            $mmiiCounte++;
        }

        $staff = '[
            {"name":"Jérôme Azé","shapes":{"bouche":{"img":"mouth1.png","color":"#990033"},"cheveux":{"img":"hair11.png","color":"#922724"},"nez":{"img":"nez1.png"},"tete":{"img":"tete1.png","color":"#ffe0bd"},"yeux":{"img":"eyes4.png","color":"#2b5e3b"},"pilosite":{"img":"pilo5.png","color":"#D4915D"},"maquillage":{"img":"0.png"},"particularites":{"img":"part11.png","color":"#B22222"},"sourcils":{"img":"brow3.png","color":"#922724"},"pull":{"img":"pull.png","color":"#FFFFFF"}},"background":"IUT_-_background_1.jpg"},
            {"name":"Augustin Rogel","shapes":{"bouche":{"img":"mouth2.png","color":"#996666"},"cheveux":{"img":"hair8.png","color":"#4A2C2A"},"nez":{"img":"nez6.png"},"tete":{"img":"tete1.png","color":"#ffd6b3"},"yeux":{"img":"eyes5.png","color":"#634e34"},"pilosite":{"img":"pilo5.png","color":"#4A2C2A"},"maquillage":{"img":"shadeye2.png"},"particularites":{"img":"0.png","color":"#FFFFFF"},"sourcils":{"img":"brow6.png","color":"#2C222B"},"pull":{"img":"pull.png","color":"#1B4D90"}},"background":"Hall_dentree_-_background_6.jpg"}
        ]';

        $staff2 = json_decode($staff, true);

        foreach ($staff2 as $s) {
            $mmii = new Mmii();
            $mmii->shape = $s['shapes'];
            $mmii->background = $s['background'];
            $mmii->save();

            CardTemplate::factory()
                            ->staff()
                            ->create([
                                'name' => $s['name'],
                                'mmii_id' => $mmii->id,
                            ]);
        }

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
        }

        $cards = [
            'Laptop Pro' => 'laptop_pro.png',
            'Tablette Graphique' => 'tablette.png',
            'Casque Audio' => 'casque.png',
            'BlackMagic' => 'black_magic.png',
            'Suite Adobe' => 'adobe.png',
            'VS Code' => 'vscode.png',
            'Café Double' => 'cafedouble.png',
            'Energy Drink' => 'energy.png',
            'Clé USB 1To' => 'usb1To.png',
            'Second Écran' => 'doublecran.png',
            'Casque VR' => 'casque.png',
            'Fond Vert' => 'fondvert.png',
            'Mac Pro' => 'macpro.png',
            'Unity' => 'unity.png',
            'Figma' => 'figma.png',
            'GitHub' => 'github.png',
            'Miku Miku beam' => 'MIKUMIKUBEAMMMM.png',
            'ChatGPT' => 'gpt.png',
            'Claude.ai' => 'claude.png',
        ];

        foreach ($cards as $name => $image) {
            CardTemplate::factory()
                ->object()
                ->create([
                    'name' => $name,
                ]);
        }

        $templates = CardTemplate::where('type', CardTypes::OBJECT)->get();
        foreach ($templates as $template) {
            CardVersion::factory()
                ->forTemplate($template->id)
                ->withRarity(CardRarity::COMMON)
                ->create([
                    'image' => $cards[$template->name],
                ]);
        }
    }
}
