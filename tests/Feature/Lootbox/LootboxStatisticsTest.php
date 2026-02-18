<?php

namespace Tests\Feature\Lootbox;

use App\Enums\CardRarity;
use App\Enums\CardTypes;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Mmii;
use App\Services\LootboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test statistique du système de lootbox.
 *
 * Simule N tirages et vérifie que la distribution observée
 * correspond aux probabilités théoriques avec une marge d'erreur acceptable.
 *
 * On utilise un intervalle de confiance à 99% (z=2.576) pour éviter
 * les faux positifs tout en détectant les vrais bugs.
 *
 * Utilise RefreshDatabase + SQLite in-memory (phpunit.xml) :
 * la BDD de test est 100% éphémère en RAM → zéro pollution de la BDD de dev.
 */
class LootboxStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private LootboxService $service;

    /** Nombre de tirages par test */
    private const DRAWS = 1000;

    /** Score Z pour un intervalle de confiance à 99% */
    private const Z_SCORE = 2.576;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LootboxService();
        $this->seedTestData();
    }

    /**
     * Seed les données de test reproduisant la structure de prod :
     * - Students level 1, 2, 3 avec versions common/uncommon/rare
     * - Staff avec versions
     * - Objects avec versions
     * - Quelques versions epic pour les templates éligibles
     */
    private function seedTestData(): void
    {
        // Créer des mmiis
        Mmii::factory(20)->create();
        $mmiis = Mmii::all();

        // Students level 1 (10 templates)
        for ($i = 0; $i < 10; $i++) {
            $template = CardTemplate::factory()
                ->student()
                ->withLevel(1)
                ->withMmii($mmiis->random())
                ->create();

            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::EPIC)->create();
        }

        // Students level 2 (10 templates)
        for ($i = 0; $i < 10; $i++) {
            $template = CardTemplate::factory()
                ->student()
                ->withLevel(2)
                ->withMmii($mmiis->random())
                ->create();

            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::UNCOMMON)->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::EPIC)->create();
        }

        // Students level 3 (10 templates)
        for ($i = 0; $i < 10; $i++) {
            $template = CardTemplate::factory()
                ->student()
                ->withLevel(3)
                ->withMmii($mmiis->random())
                ->create();

            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::RARE)->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::EPIC)->create();
        }

        // Staff (5 templates)
        for ($i = 0; $i < 5; $i++) {
            $template = CardTemplate::factory()
                ->staff()
                ->withMmii($mmiis->random())
                ->create();

            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::RARE)->create();
            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::EPIC)->create();
        }

        // Objects (5 templates)
        for ($i = 0; $i < 5; $i++) {
            $template = CardTemplate::factory()
                ->object()
                ->create();

            CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
        }
    }

    /**
     * Calcule la marge d'erreur acceptable pour une proportion observée.
     * Utilise l'intervalle de confiance binomial : z * sqrt(p*(1-p)/n)
     */
    private function marginOfError(float $expectedProportion, int $n): float
    {
        if ($expectedProportion <= 0 || $expectedProportion >= 1) {
            return 0.01; // Marge minimale pour les cas extrêmes
        }

        return self::Z_SCORE * sqrt($expectedProportion * (1 - $expectedProportion) / $n);
    }

    /**
     * Asserte qu'une proportion observée est dans l'intervalle de confiance.
     */
    private function assertProportionInRange(
        float $observed,
        float $expected,
        int $n,
        string $label
    ): void {
        $margin = $this->marginOfError($expected, $n);
        $lower = max(0, $expected - $margin);
        $upper = min(1, $expected + $margin);

        $this->assertTrue(
            $observed >= $lower && $observed <= $upper,
            sprintf(
                "%s: proportion observée %.4f hors de l'intervalle attendu [%.4f, %.4f] (attendu: %.4f, n=%d, marge=%.4f)",
                $label,
                $observed,
                $lower,
                $upper,
                $expected,
                $n,
                $margin
            )
        );
    }

    // ==========================================
    // Test 1 : Distribution des templates par slot
    // ==========================================

    /**
     * Slot 0 : 100% student level 1
     */
    public function test_slot0_always_gives_student_level1(): void
    {
        $results = ['student_1' => 0, 'other' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(0);
            $template = $version->cardTemplate;

            if ($template->type === CardTypes::STUDENT && $template->level === 1) {
                $results['student_1']++;
            } else {
                $results['other']++;
            }
        }

        $this->assertEquals(
            self::DRAWS,
            $results['student_1'],
            "Slot 0 devrait TOUJOURS donner un student level 1. " .
            "Obtenu: {$results['student_1']}/{self::DRAWS} student_1, {$results['other']} autres"
        );
    }

    /**
     * Slot 1 : 100% student level 1
     */
    public function test_slot1_always_gives_student_level1(): void
    {
        $results = ['student_1' => 0, 'other' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(1);
            $template = $version->cardTemplate;

            if ($template->type === CardTypes::STUDENT && $template->level === 1) {
                $results['student_1']++;
            } else {
                $results['other']++;
            }
        }

        $this->assertEquals(
            self::DRAWS,
            $results['student_1'],
            "Slot 1 devrait TOUJOURS donner un student level 1"
        );
    }

    /**
     * Slot 2 : 70% student L2, 25% student L3, 5% staff
     */
    public function test_slot2_template_distribution(): void
    {
        $results = ['student_2' => 0, 'student_3' => 0, 'staff' => 0, 'other' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(2);
            $template = $version->cardTemplate;

            $key = match (true) {
                $template->type === CardTypes::STUDENT && $template->level === 2 => 'student_2',
                $template->type === CardTypes::STUDENT && $template->level === 3 => 'student_3',
                $template->type === CardTypes::STAFF => 'staff',
                default => 'other',
            };
            $results[$key]++;
        }

        $this->assertEquals(0, $results['other'], "Slot 2 ne devrait jamais donner de type inattendu");

        $this->assertProportionInRange(
            $results['student_2'] / self::DRAWS,
            0.70,
            self::DRAWS,
            'Slot 2 → Student L2 (attendu: 70%)'
        );
        $this->assertProportionInRange(
            $results['student_3'] / self::DRAWS,
            0.25,
            self::DRAWS,
            'Slot 2 → Student L3 (attendu: 25%)'
        );
        $this->assertProportionInRange(
            $results['staff'] / self::DRAWS,
            0.05,
            self::DRAWS,
            'Slot 2 → Staff (attendu: 5%)'
        );

        // Log pour visibilité
        echo "\n  Slot 2 distribution (n=" . self::DRAWS . "):\n";
        foreach ($results as $key => $count) {
            printf("    %-12s: %4d (%5.1f%%)\n", $key, $count, $count / self::DRAWS * 100);
        }
    }

    /**
     * Slot 3 : 60% student L2, 35% student L3, 5% staff
     */
    public function test_slot3_template_distribution(): void
    {
        $results = ['student_2' => 0, 'student_3' => 0, 'staff' => 0, 'other' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(3);
            $template = $version->cardTemplate;

            $key = match (true) {
                $template->type === CardTypes::STUDENT && $template->level === 2 => 'student_2',
                $template->type === CardTypes::STUDENT && $template->level === 3 => 'student_3',
                $template->type === CardTypes::STAFF => 'staff',
                default => 'other',
            };
            $results[$key]++;
        }

        $this->assertEquals(0, $results['other'], "Slot 3 ne devrait jamais donner de type inattendu");

        $this->assertProportionInRange(
            $results['student_2'] / self::DRAWS,
            0.60,
            self::DRAWS,
            'Slot 3 → Student L2 (attendu: 60%)'
        );
        $this->assertProportionInRange(
            $results['student_3'] / self::DRAWS,
            0.35,
            self::DRAWS,
            'Slot 3 → Student L3 (attendu: 35%)'
        );
        $this->assertProportionInRange(
            $results['staff'] / self::DRAWS,
            0.05,
            self::DRAWS,
            'Slot 3 → Staff (attendu: 5%)'
        );

        echo "\n  Slot 3 distribution (n=" . self::DRAWS . "):\n";
        foreach ($results as $key => $count) {
            printf("    %-12s: %4d (%5.1f%%)\n", $key, $count, $count / self::DRAWS * 100);
        }
    }

    /**
     * Slot 4 : 20% staff, 80% object
     */
    public function test_slot4_template_distribution(): void
    {
        $results = ['staff' => 0, 'object' => 0, 'other' => 0];

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(4);
            $template = $version->cardTemplate;

            $key = match (true) {
                $template->type === CardTypes::STAFF => 'staff',
                $template->type === CardTypes::OBJECT => 'object',
                default => 'other',
            };
            $results[$key]++;
        }

        $this->assertEquals(0, $results['other'], "Slot 4 ne devrait jamais donner de type inattendu");

        $this->assertProportionInRange(
            $results['staff'] / self::DRAWS,
            0.20,
            self::DRAWS,
            'Slot 4 → Staff (attendu: 20%)'
        );
        $this->assertProportionInRange(
            $results['object'] / self::DRAWS,
            0.80,
            self::DRAWS,
            'Slot 4 → Object (attendu: 80%)'
        );

        echo "\n  Slot 4 distribution (n=" . self::DRAWS . "):\n";
        foreach ($results as $key => $count) {
            printf("    %-12s: %4d (%5.1f%%)\n", $key, $count, $count / self::DRAWS * 100);
        }
    }

    // ==========================================
    // Test 2 : Distribution des raretés (versions)
    // ==========================================

    /**
     * Vérifie la distribution des raretés pour les templates student L1.
     * Attendu : common=70/(70+2)≈97.2%, epic=2/(70+2)≈2.8%
     * (pondération basée sur dropRate: common=0.70, epic=0.02)
     */
    public function test_rarity_distribution_student_level1(): void
    {
        $results = [];
        foreach (CardRarity::cases() as $r) {
            $results[$r->value] = 0;
        }

        // Tirer uniquement depuis le slot 0 (100% student L1)
        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(0);
            $results[$version->rarity->value]++;
        }

        $expectedCommon = 0.70 / (0.70 + 0.02); // ~97.22%
        $expectedEpic = 0.02 / (0.70 + 0.02);   // ~2.78%

        $this->assertProportionInRange(
            $results['common'] / self::DRAWS,
            $expectedCommon,
            self::DRAWS,
            'Student L1 → Common (attendu: ~97.2%)'
        );
        $this->assertProportionInRange(
            $results['epic'] / self::DRAWS,
            $expectedEpic,
            self::DRAWS,
            'Student L1 → Epic (attendu: ~2.8%)'
        );

        echo "\n  Rarity distribution Student L1 (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            if ($count > 0) {
                printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
            }
        }
    }

    /**
     * Vérifie la distribution des raretés pour les templates student L2.
     * Attendu : uncommon=20/(20+2)≈90.9%, epic=2/(20+2)≈9.1%
     */
    public function test_rarity_distribution_student_level2(): void
    {
        // On cible uniquement les templates student L2
        $templateIds = CardTemplate::where('type', CardTypes::STUDENT)
            ->where('level', 2)
            ->pluck('id');

        $results = [];
        foreach (CardRarity::cases() as $r) {
            $results[$r->value] = 0;
        }

        for ($i = 0; $i < self::DRAWS; $i++) {
            // Le slot 2 a 70% chance de student L2, on force un tirage jusqu'à avoir un L2
            do {
                $version = $this->service->generateLoot(2);
            } while (!$templateIds->contains($version->cardTemplate->id));

            $results[$version->rarity->value]++;
        }

        $expectedUncommon = 0.20 / (0.20 + 0.02); // ~90.91%
        $expectedEpic = 0.02 / (0.20 + 0.02);     // ~9.09%

        $this->assertProportionInRange(
            $results['uncommon'] / self::DRAWS,
            $expectedUncommon,
            self::DRAWS,
            'Student L2 → Uncommon (attendu: ~90.9%)'
        );
        $this->assertProportionInRange(
            $results['epic'] / self::DRAWS,
            $expectedEpic,
            self::DRAWS,
            'Student L2 → Epic (attendu: ~9.1%)'
        );

        echo "\n  Rarity distribution Student L2 (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            if ($count > 0) {
                printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
            }
        }
    }

    /**
     * Vérifie la distribution des raretés pour les templates student L3.
     * Attendu : rare=8/(8+2)=80%, epic=2/(8+2)=20%
     */
    public function test_rarity_distribution_student_level3(): void
    {
        $templateIds = CardTemplate::where('type', CardTypes::STUDENT)
            ->where('level', 3)
            ->pluck('id');

        $results = [];
        foreach (CardRarity::cases() as $r) {
            $results[$r->value] = 0;
        }

        for ($i = 0; $i < self::DRAWS; $i++) {
            do {
                $version = $this->service->generateLoot(2);
            } while (!$templateIds->contains($version->cardTemplate->id));

            $results[$version->rarity->value]++;
        }

        $expectedRare = 0.08 / (0.08 + 0.02); // 80%
        $expectedEpic = 0.02 / (0.08 + 0.02); // 20%

        $this->assertProportionInRange(
            $results['rare'] / self::DRAWS,
            $expectedRare,
            self::DRAWS,
            'Student L3 → Rare (attendu: 80%)'
        );
        $this->assertProportionInRange(
            $results['epic'] / self::DRAWS,
            $expectedEpic,
            self::DRAWS,
            'Student L3 → Epic (attendu: 20%)'
        );

        echo "\n  Rarity distribution Student L3 (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            if ($count > 0) {
                printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
            }
        }
    }

    /**
     * Vérifie la distribution des raretés pour les templates staff.
     * Attendu : rare=8/(8+2)=80%, epic=2/(8+2)=20%
     */
    public function test_rarity_distribution_staff(): void
    {
        $templateIds = CardTemplate::where('type', CardTypes::STAFF)
            ->pluck('id');

        $results = [];
        foreach (CardRarity::cases() as $r) {
            $results[$r->value] = 0;
        }

        for ($i = 0; $i < self::DRAWS; $i++) {
            do {
                $version = $this->service->generateLoot(4);
            } while (!$templateIds->contains($version->cardTemplate->id));

            $results[$version->rarity->value]++;
        }

        $expectedRare = 0.08 / (0.08 + 0.02); // 80%
        $expectedEpic = 0.02 / (0.08 + 0.02); // 20%

        $this->assertProportionInRange(
            $results['rare'] / self::DRAWS,
            $expectedRare,
            self::DRAWS,
            'Staff → Rare (attendu: 80%)'
        );
        $this->assertProportionInRange(
            $results['epic'] / self::DRAWS,
            $expectedEpic,
            self::DRAWS,
            'Staff → Epic (attendu: 20%)'
        );

        echo "\n  Rarity distribution Staff (n=" . self::DRAWS . "):\n";
        foreach ($results as $rarity => $count) {
            if ($count > 0) {
                printf("    %-12s: %4d (%5.1f%%)\n", $rarity, $count, $count / self::DRAWS * 100);
            }
        }
    }

    // ==========================================
    // Test 3 : Simulation complète d'une lootbox
    // ==========================================

    /**
     * Simule 1000 lootboxes complètes (5 cartes chacune = 5000 cartes)
     * et affiche un rapport complet de la distribution.
     */
    public function test_full_lootbox_simulation(): void
    {
        $totalLootboxes = self::DRAWS;
        $totalCards = $totalLootboxes * 5;

        $templateDistribution = [];
        $rarityDistribution = [];
        $slotDistribution = [0 => [], 1 => [], 2 => [], 3 => [], 4 => []];

        foreach (CardRarity::cases() as $r) {
            $rarityDistribution[$r->value] = 0;
        }

        for ($i = 0; $i < $totalLootboxes; $i++) {
            $lootbox = $this->service->generateLootbox();

            foreach ($lootbox as $slotIndex => $version) {
                $template = $version->cardTemplate;
                $key = $template->type->value . '_' . ($template->level ?? 'null');

                // Template distribution globale
                $templateDistribution[$key] = ($templateDistribution[$key] ?? 0) + 1;

                // Rarity distribution globale
                $rarityDistribution[$version->rarity->value]++;

                // Distribution par slot
                $slotKey = $template->type->value . '_' . ($template->level ?? 'null') . '_' . $version->rarity->value;
                $slotDistribution[$slotIndex][$slotKey] = ($slotDistribution[$slotIndex][$slotKey] ?? 0) + 1;
            }
        }

        // === Assertions globales ===

        // On doit avoir au moins quelques epic dans 5000 cartes
        $this->assertGreaterThan(
            0,
            $rarityDistribution['epic'] ?? 0,
            "Sur {$totalCards} cartes tirées, on devrait avoir AU MOINS une carte epic !"
        );

        // On ne doit JAMAIS avoir de legendary (dropRate = 0)
        $this->assertEquals(
            0,
            $rarityDistribution['legendary'] ?? 0,
            "Aucune carte legendary ne devrait apparaître (dropRate = 0)"
        );

        // === Rapport détaillé ===
        echo "\n\n  ╔══════════════════════════════════════════════════════════╗\n";
        echo "  ║        RAPPORT DE SIMULATION LOOTBOX                    ║\n";
        echo "  ║        {$totalLootboxes} lootboxes = {$totalCards} cartes                    ║\n";
        echo "  ╚══════════════════════════════════════════════════════════╝\n\n";

        echo "  📊 Distribution globale des types:\n";
        echo "  ─────────────────────────────────────\n";
        arsort($templateDistribution);
        foreach ($templateDistribution as $type => $count) {
            printf("    %-20s: %5d (%5.1f%%)\n", $type, $count, $count / $totalCards * 100);
        }

        echo "\n  💎 Distribution globale des raretés:\n";
        echo "  ─────────────────────────────────────\n";
        foreach ($rarityDistribution as $rarity => $count) {
            $bar = str_repeat('█', (int) ($count / $totalCards * 100));
            printf("    %-12s: %5d (%5.1f%%) %s\n", $rarity, $count, $count / $totalCards * 100, $bar);
        }

        echo "\n  🎰 Distribution par slot:\n";
        echo "  ─────────────────────────────────────\n";
        foreach ($slotDistribution as $slot => $types) {
            echo "    Slot {$slot}:\n";
            arsort($types);
            foreach ($types as $key => $count) {
                printf("      %-30s: %5d (%5.1f%%)\n", $key, $count, $count / $totalLootboxes * 100);
            }
        }

        $this->assertTrue(true); // Le test passe si on arrive ici sans exception
    }

    // ==========================================
    // Test 4 : Cas limites
    // ==========================================

    /**
     * Vérifie qu'un template avec une seule version retourne toujours cette version.
     * (Cas des objets qui n'ont que common)
     */
    public function test_single_version_template_always_returns_that_version(): void
    {
        $objectVersions = [];

        for ($i = 0; $i < 100; $i++) {
            $version = $this->service->generateLoot(4);
            $template = $version->cardTemplate;

            if ($template->type === CardTypes::OBJECT) {
                $objectVersions[] = $version->rarity->value;
            }
        }

        if (count($objectVersions) > 0) {
            $allCommon = collect($objectVersions)->every(fn($r) => $r === 'common');
            $this->assertTrue(
                $allCommon,
                "Les objets n'ont qu'une version common, on ne devrait jamais avoir autre chose"
            );
        }
    }

    /**
     * Vérifie que les legendary ne tombent JAMAIS (dropRate = 0).
     */
    public function test_legendary_never_drops(): void
    {
        // Ajouter une version legendary à un template pour tester
        $template = CardTemplate::where('type', CardTypes::STUDENT)
            ->where('level', 1)
            ->first();

        CardVersion::factory()
            ->forTemplate($template->id)
            ->withRarity(CardRarity::LEGENDARY)
            ->create();

        $legendaryCount = 0;

        for ($i = 0; $i < self::DRAWS; $i++) {
            $version = $this->service->generateLoot(0);
            if ($version->rarity === CardRarity::LEGENDARY) {
                $legendaryCount++;
            }
        }

        $this->assertEquals(
            0,
            $legendaryCount,
            "Les cartes legendary (dropRate=0) ne devraient JAMAIS tomber. Obtenu: {$legendaryCount}/{self::DRAWS}"
        );
    }
}
