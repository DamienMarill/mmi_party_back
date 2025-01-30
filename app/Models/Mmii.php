<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mmii extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'shape',
        'background',
    ];

    protected $casts = [
        'shape' => 'json',
    ];

    public function rules()
    {
        return [
            'image' => 'required|string',
            'shape' => 'required|array',
            'background' => 'required|string',
        ];
    }

    public function cardTemplate()
    {
        return $this->hasOne(CardTemplate::class);
    }

    public function baseUser()
    {
        return $this->hasOne(User::class);
    }
}
