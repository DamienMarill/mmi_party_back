<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PlaceholderService
{
    private array $urls = [
        "https://picsum.photos/%d/%d",
        "https://source.unsplash.com/random/%dx%d",
        "https://api.lorem.space/image/face?w=%d&h=%d"
    ];

    public function getRandomImage(int $width = 512, int $height = 512): string
    {
        $randomUrl = $this->urls[array_rand($this->urls)];
        $url = sprintf($randomUrl, $width, $height);

        try {
            $response = Http::get($url);
            return base64_encode($response->body());
        } catch (\Exception $e) {
            // Fallback vers une URL statique en cas d'échec
            $fallbackResponse = Http::get(
                sprintf("https://api.lorem.space/image/face?w=%d&h=%d", $width, $height)
            );
            return base64_encode($fallbackResponse->body());
        }
    }
}
