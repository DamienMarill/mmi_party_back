<?php
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;

$user = User::where('name', 'like', '%lily%')
    ->orWhere('email', 'like', '%lily%')
    ->first();

if (!$user) {
    echo "Utilisateur 'lily' introuvable.\n";
    exit;
}

// Révoquer les anciens refresh tokens
RefreshToken::where('user_id', $user->id)
    ->where('revoked', false)
    ->update(['revoked' => true]);

// Créer un nouveau refresh token valide 30 jours
$refreshToken = RefreshToken::create([
    'user_id'    => $user->id,
    'token'      => Str::random(64),
    'expires_at' => now()->addDays(30),
]);

$payload = json_encode(['refresh_token' => $refreshToken->token]);

echo "\n=== LILY-MAI : {$user->name} ===\n";
echo "Clé localStorage : auth_token\n";
echo "Valeur : {$payload}\n\n";
