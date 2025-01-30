<?php

namespace Database\Seeders;

use App\Models\Mmii;
use Database\Factories\MmiiFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OneMMII extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mmiis = Mmii::all();
        $factory = new MmiiFactory();

        foreach ($mmiis as $mmii){
            $mmii->update([
                'image' => $factory->getRandomBackground(),
                'shape' => [
                    'bouche' => [
                        'img' => $factory->getRandomFile('bouche'),
                        'color' => $factory->getRandomColor('bouche')
                    ],
                    'nez' => [
                        'img' => $factory->getRandomFile('nez')
                    ],
                    'tete' => [
                        'img' => $factory->getRandomFile('tete'),
                        'color' => $factory->getRandomColor('tete')
                    ],
                    'yeux' => [
                        'img' => $factory->getRandomFile('yeux'),
                        'color' => $factory->getRandomColor('yeux')
                    ],
                    'cheveux' => [
                        'img' => $factory->getRandomFile('cheveux'),
                        'color' => $factory->getRandomColor('cheveux')
                    ],
                    'maquillage' => [
                        'img' => $factory->getRandomFile('maquillage')
                    ],
                    'particularites' => [
                        'img' => $factory->getRandomFile('particularites')
                    ],
                    'pilosite' => [
                        'img' => $factory->getRandomFile('pilosite'),
                        'color' => $factory->getRandomColor('pilosite')
                    ]
                ]
            ]);
        }
    }
}
