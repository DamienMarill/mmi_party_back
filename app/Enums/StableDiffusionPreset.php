<?php

namespace App\Enums;

enum StableDiffusionPreset: string
{
    case WATERCOLOR = 'watercolor';
    case PENCIL = 'pencil';
    case GHIBLI = 'Ghibli';
    case ONE_PIECE = 'One Piece';
    case JOJO = 'Jojo';
    case MAKOTO_SHINKAI = 'Makoto Shinkai';
    case DISNEY_PIXAR = 'Disney Pixar';
    case COMICS = 'comics';
    case POP_ART = 'pop art';
    case CYBERPUNK = 'cyberpunk';
    case PIXEL_ART = 'pixel art';
    case LEAGUE_OF_LEGENDS = 'League of Legends';
    case DDD_REALISTIC = '3D realistic';
    case PS1 = 'PS1';

    public function getConfiguration(): array
    {
        return match($this) {
            self::WATERCOLOR => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => ' (high quality, best quality), masterpiece, {tags}, portrait, watercolor style, soft edges, color bleeding, artistic, painterly, subtle color gradients',
                'negative_prompt' => '(worst quality, low quality:1.4), (bad anatomy), (inaccurate limb:1.2), bad composition, inaccurate eyes, extra digit, fewer digits, (extra arms:1.2), (bad hands), (missing fingers), text, watermark, signature, bad proportions, sharp edges, digital art, harsh lines ',
                'loras' => [
                    ['name' => 'watercolor_20230906151716', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::PENCIL => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => ' masterpiece, high quality, {tags},portrait, sketch style, (pencil drawing:1.3), hand drawn, rough strokes, (graphite:1.2), detailed linework, artistic, dynamic lines, (hatching and crosshatching:1.2), traditional media, gestural drawing',
                'negative_prompt' => 'easynegative,  (worst quality, low quality:1.4), watermark, text, signature, (digital art:1.3), (colors:1.4), (paintings:1.2), clean edges, smooth shading, vector art, perfect lines, bad anatomy, bad hands, bad proportions, colored',
                'loras' => [
                    ['name' => 'Pencil_Sketch', 'weight' => 1.4],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::GHIBLI => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => '(high quality, best quality), masterpiece, professional, {tags}, portrait, soft lighting, ghibli style, hayao miyazaki style, warm colors, gentle expression, detailed backgrounds, natural lighting',
                'negative_prompt' => 'easynegative,  (worst quality, low quality:1.4), (bad anatomy), (inaccurate limb:1.2), bad composition, inaccurate eyes, extra digit, fewer digits, (extra arms:1.2), (bad hands), (missing fingers), text, watermark, signature, bad proportions, oversaturated, harsh shadows, dark theme, excessive details',
                'loras' => [
                    ['name' => 'ghibli_style_offset', 'weight' => 1],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::ONE_PIECE => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, eiichiro oda style, vibrant colors, (expressive features:1.2), bold outlines, dynamic shading, manga style, strong expressions, cartoony proportions, adventure anime',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, realistic, photorealistic, (semi-realistic:1.3), rotoscope, fine details, too many details, bad anatomy, bad hands, bad proportions, ugly, blurry',
                'loras' => [
                    ['name' => 'wanostyle_2_offset', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::JOJO => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, araki hirohiko style, (dramatic shading:1.3), strong shadows, intense expression, bold lines, (muscular:1.1), sharp features, fashion model pose, flamboyant, menacing, ink shading',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, cute, chibi, (soft shading:1.3), (gentle:1.3), simple background, plain, realistic, photorealistic, bad anatomy, bad hands, bad proportions, ugly',
                'loras' => [
                    ['name' => 'diamond_wa_kudakenai_2', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::MAKOTO_SHINKAI => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, cinematic lighting, detailed background,  volumetric lighting, lens flare,  detailed eyes, atmospheric, golden hour, dramatic shadows, glowing particles',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, flat color, flat shading, (simple background:1.3), chibi, cartoon, western cartoon, bad anatomy, bad hands, bad proportions, ugly, blurry',
                'loras' => [
                    ['name' => 'wanostyle_2_offset', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::DISNEY_PIXAR => [
                'model' => 'disneyPixarCartoon_v10.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, modern animation, subsurface scattering, smooth shading, detailed skin texture, expressive eyes, (volumetric lighting:1.2), hdri rendering, cute, appealing character design, high detail face',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, (2d:1.3), cel shading, anime, sketch, traditional art, line art, flat colors, bad anatomy, (realistic:1.3), photorealistic, bad face, ugly, bad proportions, (human skin:1.2), bad hands',
                'loras' => [],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::COMICS => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, (american comic style:1), comic book art, dynamic lighting, bold colors, strong outlines, (cel shading:1.2), superhero style, dramatic shadows, comic book panel, clean linework, detailed face, professional illustration',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, (realistic:1.2), (3d:1.3), anime, manga, pencil sketch, (painting:1.2), watercolor, photos, bad anatomy, bad hands, bad proportions, (particle effects:1.2), lens flare',
                'loras' => [
                    ['name' => 'ComiDusk', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::POP_ART => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, (graffiti art:1.4), street art style, spray paint, urban art, bold colors, stylized, (dripping paint:1.2), wild style, dynamic, street culture, neon colors, contemporary, urban contemporary',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, realistic, timid lines, (watercolor:1.3), (traditional art:1.2), (gentle:1.3), subtle, minimalist, bad anatomy, bad hands, bad proportions, ugly',
                'loras' => [
                    ['name' => 'pop_art_v2', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::CYBERPUNK => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, (cyberpunk:1.3), neon lights, (cyber implants:1.1), (holographic:1.2), technological, futuristic, night city, (leather:0.8), (metallic details:1.1), (glowing:1.2), (synthwave colors:0.8), (cyborg:0.8), rain, city lights, (subsurface scattering:1.1), (backlight:1.2)',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, (natural:1.2), (soft lighting:1.3), (daylight:1.3), (muted colors:1.4), (plain:1.3), (traditional:1.2), bad anatomy, bad hands, bad proportions, (vintage:1.3), blurry',
                'loras' => [
                    ['name' => 'CyberPunkAI', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::PIXEL_ART => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, (pixel art:1.4), retro game style, 16-bit, pixel perfect, clear edges, limited color palette, game sprite, (pixelated:1.3), snes style, sharp pixels',
                'negative_prompt' => 'easynegative,  (worst quality, low quality:1.4), watermark, text, signature, blurry, smooth edges, (realistic:1.4), (3d:1.4), gradient colors, anti-aliasing, bad anatomy, bad proportions',
                'loras' => [
                    ['name' => 'PX64NOCAP', 'weight' => 1],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::LEAGUE_OF_LEGENDS => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, (splash art:1.3), fantasy style, dramatic lighting, highly detailed, dynamic pose, (league of legends style:1.4), intense colors, epic atmosphere, high contrast, detailed face',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, simple, flat colors, pixel art, chibi, (anime:1.2), bad anatomy, bad hands, bad proportions',
                'loras' => [
                    ['name' => 'LoL_Splash_V2', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::DDD_REALISTIC => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, high quality, {tags}, portrait, (3d render:1.4), game asset, pbr texturing, subsurface scattering, realistic lighting, ambient occlusion, (unreal engine:1.2), modern game graphics, detailed textures',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, anime, cartoon, pixel art, flat colors, 2d, bad anatomy, bad proportions',
                'loras' => [
                    ['name' => 'lou', 'weight' => 1],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
            self::PS1 => [
                'model' => 'anyloraCheckpoint_bakedvaeBlessedFp16.safetensors',
                'base_prompt' => 'masterpiece, {tags}, portrait, (ps1 graphics:1.4), low poly, retro 3d, (ps1 style:1.3), vertex colored, no textures, sharp edges, (low resolution textures:1.2), polygon style, retro game aesthetic',
                'negative_prompt' => 'easynegative, (worst quality, low quality:1.4), watermark, text, signature, high poly, detailed textures, smooth shading, (realistic:1.3), modern graphics, raytracing, bad anatomy',
                'loras' => [
                    ['name' => 'RW_PS1v1', 'weight' => 0.8],
                    ['name' => 'hyperdetailer_v095', 'weight' => 0.3],
                ],
                'sampler_name' => 'Euler a',
                'cfg_scale' => 5.5
            ],
        };
    }

    public function validateConfiguration(): bool
    {
        $config = $this->getConfiguration();
        $requiredKeys = ['model', 'base_prompt', 'negative_prompt', 'loras'];

        return empty(array_diff($requiredKeys, array_keys($config)));
    }
}
