<?php

namespace App\Providers\Faker;

use Faker\Provider\Base;

class ImageProvider extends Base
{
    public function waifuPlaceholder(int $width = 400, int $height = 400): string
    {
        return "https://placewaifu.com/image/{$width}/{$height}";
    }
}
