<?php

namespace Tests\Feature\Lootbox;

use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Tests\TestCase;

/**
 * Garde-fou contre la régression Laravel #58520 sur le tirage aléatoire des cartes.
 *
 * CONTEXTE — pourquoi ce n'est PAS un test de distribution :
 * En prod (MySQL), inRandomOrder() SANS argument compile en `RAND(0)`, car le seed
 * par défaut vaut '' et MySqlGrammar::compileRandom() fait (int)'' === 0 → seed figé
 * → ordre identique à chaque requête → toujours la même carte pour une rareté donnée.
 * Or la suite de tests tourne sur SQLite (:memory:), dont la grammaire IGNORE le seed
 * et génère `RANDOM()` : le bug est donc TOTALEMENT INVISIBLE au runtime en test.
 * Un test de distribution passerait donc au vert malgré le bug — inutile comme garde-fou.
 *
 * On vérifie donc directement le vecteur de régression :
 *   1. le contrat de la grammaire MySQL (RAND(0) sans seed vs RAND(seed) avec seed) ;
 *   2. que LootboxService ne réintroduise jamais un inRandomOrder() sans seed.
 *
 * Historique : corrigé en mars (commit a786839), réintroduit par erreur le 23/09
 * (single-query refactor) → tous les drops se sont mis à se répéter en prod.
 */
class LootboxRandomnessTest extends TestCase
{
    public function test_mysql_grammar_seeds_rand_with_zero_when_no_seed_is_given(): void
    {
        $grammar = new MySqlGrammar;

        // Le piège : sans seed, on obtient RAND(0), donc un ordre déterministe (#58520).
        $this->assertSame('RAND(0)', $grammar->compileRandom(''));

        // Le fix : un seed entier non nul produit un ordre qui varie d'un tirage à l'autre.
        $this->assertSame('RAND(123456)', $grammar->compileRandom(123456));
        $this->assertNotSame('RAND(0)', $grammar->compileRandom(123456));
    }

    public function test_lootbox_service_never_uses_a_seedless_random_order(): void
    {
        $source = file_get_contents(app_path('Services/LootboxService.php'));

        $this->assertDoesNotMatchRegularExpression(
            '/->\s*inRandomOrder\(\s*(0\s*)?\)/',
            $source,
            'LootboxService ne doit JAMAIS appeler inRandomOrder() sans seed (ou avec 0) : '
            .'sur MySQL cela compile en RAND(0) et fige le tirage (toujours la même carte). '
            .'Utiliser inRandomOrder(random_int(1, PHP_INT_MAX)). Voir Laravel #58520.'
        );
    }
}
