<?php

namespace App\Services;

use App\Enums\StableDiffusionPreset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StableDiffusionService
{
    private string $sdUrl;
    private string $deepbooru_url;

    public function __construct()
    {
        $this->sdUrl = config('services.stable_diffusion.url', 'http://localhost:7860/sdapi/v1');
        $this->deepbooru_url = config('services.deepbooru.url', 'http://localhost:7860/sdapi/v1');
    }

    private function buildEnrichedPrompt(string $basePrompt, array $tags): string
    {
        // On construit la chaîne de tags
        $tagString = implode(', ', array_keys($tags));

        // On remplace le placeholder {tags} par les tags réels
        $prompt = str_replace('{tags}', $tagString, $basePrompt);

        return $prompt;
    }

    public function img2img(
        string $base64Image,
        StableDiffusionPreset $preset = StableDiffusionPreset::WATERCOLOR,
        float $denoising_strength = 0.5,
        int $steps = 20
    ): array {
        try {
            if (!$preset->validateConfiguration()) {
                throw new \InvalidArgumentException("Invalid preset configuration");
            }

            $tags = $this->analyzeImage($base64Image);
            $relevantTags = $this->filterRelevantTags($tags);

            $config = $preset->getConfiguration();

            // Construction du prompt avec les tags
            $prompt = $this->buildEnrichedPrompt($config['base_prompt'], $relevantTags);

            // Ajout des LoRAs directement après le prompt
            $loraString = $this->buildLoraString($config['loras']);
            $finalPrompt = $prompt . ' ' . $loraString;

            $payload = [
                'init_images' => [$base64Image],
                'prompt' => $finalPrompt,
                'negative_prompt' => $config['negative_prompt'],
                'denoising_strength' => $denoising_strength,
                'steps' => $steps,
                'cfg_scale' => $config['cfg_scale'] ?? 7,
                'width' => 512,
                'height' => 512,
                'restore_faces' => false,
                'sampler_name' => $config['sampler_name'] ?? 'DPM++ 2M Karras',
                'override_settings' => [
                    'sd_model_checkpoint' => $config['model']
                ]
            ];

            // Log du prompt final pour debug
            Log::debug('Final prompt', ['prompt' => $finalPrompt]);

            $response = Http::post("{$this->sdUrl}/sdapi/v1/img2img", $payload);

            if (!$response->successful()) {
                throw new \Exception("img2img request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("img2img processing failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function analyzeImage(string $base64Image): array
    {
        try {
            $response = Http::post("{$this->deepbooru_url}/interrogate", [
                'image' => $base64Image,
                'model' => 'deepbooru'
            ]);

            if (!$response->successful()) {
                throw new \Exception("Deepbooru request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Deepbooru analysis failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function filterRelevantTags(array $tags, float $threshold = 0.5): array
    {
        return array_filter($tags, fn($confidence) => $confidence > $threshold);
    }

    private function buildLoraString(array $loras): string
    {
        return implode('', array_map(
            fn($lora) => "<lora:{$lora['name']}:{$lora['weight']}>",
            $loras
        ));
    }
}
