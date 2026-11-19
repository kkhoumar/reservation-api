<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToRestaurant, HasFactory;

    /** Famille de la catégorie : sert à séparer les totaux du comptage. */
    public const KINDS = [
        'drink' => 'Boissons',
        'food' => 'Nourriture',
    ];

    protected $fillable = ['restaurant_id', 'name', 'kind', 'sort_order'];

    protected static function booted(): void
    {
        // Sans choix explicite, on devine d'après le nom (« Bières » → boissons)
        static::creating(function (Category $category) {
            if (! array_key_exists($category->kind, self::KINDS)) {
                $category->kind = self::guessKind((string) $category->name);
            }
        });
    }

    /** « Boissons », « Bières », « Sucreries », « BOCK 66 »… → drink ; le reste → food. */
    public static function guessKind(string $name): string
    {
        $pattern = '/(?<![\p{L}])(boiss?ons?|bi[eè]res?|bock|vins?|jus|sucreries?|sodas?|eaux?|alcools?|liqueurs?|whisky|cocktails?|champagnes?|ap[eé]ritifs?|gin|rhum|vodka|cidres?|flag|castel|guinness|drinks?)(?![\p{L}])/iu';

        return preg_match($pattern, $name) ? 'drink' : 'food';
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? self::KINDS['food'];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
