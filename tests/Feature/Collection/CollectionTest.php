<?php

namespace Tests\Feature\Collection;

use App\Enums\CardRarity;
use App\Enums\LootboxTypes;
use App\Models\CardInstance;
use App\Models\CardTemplate;
use App\Models\CardVersion;
use App\Models\Lootbox;
use App\Models\Mmii;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->mmi1()
            ->create(['email_verified_at' => now()]);

        // Authentifier l'utilisateur
        $this->token = auth()->fromUser($this->user);
    }

    private function createCardForUser(): CardInstance
    {
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();
        $version = CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
        $lootbox = Lootbox::create([
            'user_id' => $this->user->id,
            'type' => LootboxTypes::QUOTIDIAN,
        ]);

        return CardInstance::create([
            'card_version_id' => $version->id,
            'lootbox_id' => $lootbox->id,
            'user_id' => $this->user->id,
        ]);
    }

    // ========== Tests d'acces ==========

    public function test_unauthenticated_user_cannot_access_collection(): void
    {
        $response = $this->getJson('/api/collection');

        $response->assertStatus(401);
    }

    public function test_unverified_user_cannot_access_collection(): void
    {
        $unverifiedUser = User::factory()->unverified()->create();
        $token = auth()->fromUser($unverifiedUser);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/collection');

        // Le middleware EnsureEmailIsVerifiedApi renvoie 409 (Conflict) pour les non-verifies
        $response->assertStatus(409);
    }

    public function test_verified_user_can_access_collection(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/collection');

        $response->assertStatus(200);
    }

    // ========== Tests de liste ==========

    public function test_collection_returns_empty_for_new_user(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/collection');

        $response->assertStatus(200)
                 ->assertJsonCount(0);
    }

    public function test_collection_returns_user_cards(): void
    {
        $card = $this->createCardForUser();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/collection');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    public function test_collection_does_not_return_other_user_cards(): void
    {
        // Creer une carte pour un autre utilisateur
        $otherUser = User::factory()->mmi1()->create();
        $mmii = Mmii::factory()->create();
        $template = CardTemplate::factory()->student()->withLevel(1)->withMmii($mmii)->create();
        $version = CardVersion::factory()->forTemplate($template->id)->withRarity(CardRarity::COMMON)->create();
        $lootbox = Lootbox::create([
            'user_id' => $otherUser->id,
            'type' => LootboxTypes::QUOTIDIAN,
        ]);
        CardInstance::create([
            'card_version_id' => $version->id,
            'lootbox_id' => $lootbox->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/collection');

        $response->assertStatus(200)
                 ->assertJsonCount(0);
    }

    // ========== Tests de detail ==========

    public function test_can_view_single_card(): void
    {
        $card = $this->createCardForUser();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson("/api/collection/{$card->cardVersion->id}");

        $response->assertStatus(200);
    }

    public function test_cannot_view_nonexistent_card(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                         ->getJson('/api/collection/nonexistent-uuid');

        $response->assertStatus(404);
    }
}
