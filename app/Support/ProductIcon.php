<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Petites images des produits (dessins maison, sans marque) et choix
 * automatique d'après le nom : « Flag » → bière verte, « Coca » → sucrerie,
 * « Poulet braisé » dans une catégorie Nourriture → plat.
 */
class ProductIcon
{
    /** clé => [libellé, couleur, dessin] */
    public const ICONS = [
        'biere_brune' => ['Bière brune', '#6B3A1E', 'biere'],
        'biere_verte' => ['Bière verte', '#1F6B3B', 'biere'],
        'grande' => ['Grande bouteille', '#5A2F17', 'grande'],
        'canette' => ['Canette', '#C62D2D', 'canette'],
        'sucrerie' => ['Sucrerie', '#E8811F', 'sucrerie'],
        'eau' => ['Eau', '#9CCBF0', 'eau'],
        'jus' => ['Jus', '#E8A21F', 'jus'],
        'vin' => ['Vin', '#7A1F35', 'vin'],
        'liqueur' => ['Liqueur', '#B9782A', 'verre'],
        'plat' => ['Plat', '#B5651D', 'plat'],
        'boisson' => ['Autre boisson', '#2E8B6E', 'gobelet'],
    ];

    /** Mots qui désignent chaque dessin (dans le nom du produit ou de la catégorie). L'ordre compte. */
    private const RULES = [
        'eau' => 'eaux?|awa|cristal(ine)?|olgane|minerale',
        'vin' => 'vins?|champagnes?|mousseux|sangria|rose|moet',
        'canette' => 'canettes?|cannettes?|booster|red ?bull|energy|xxl|monster|predator|burn',
        'liqueur' => 'whisky|whiskey|gin|vodka|pastis|ricard|rhum|cognac|liqueurs?|koutoukou|baileys|martini|doses?|label|hennessy|jack|spiritueux|alcools?|aperitifs?',
        'jus' => 'jus|bissap|gnamakoudji|gingembre|tamarin|passion|dafani|cocktails?|nectar|milk ?shake',
        'biere_verte' => 'flag|heineken|tuborg|carlsberg|becks?',
        'biere_brune' => 'bieres?|beers?|bock|castel|beaufort|33|export|ivoire|mutzig|number one|doppel|guinness|desperados|budweiser|corona|class|isenberg|pils|lager|chill|asso|malta',
        'sucrerie' => 'coca|fanta|sprite|top|pepsi|schweppes|tonic|youzou|sucreries?|sodas?|orangina|vimto|7 ?up|cola|limonade|softs?',
    ];

    public static function isValid(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::ICONS);
    }

    /** Le dessin d'un produit : celui choisi par le patron, sinon le choix automatique. */
    public static function keyFor(?string $chosen, string $name, ?string $categoryName, ?string $kind): string
    {
        return self::isValid($chosen) ? $chosen : self::guess($name, $categoryName, $kind);
    }

    public static function guess(string $name, ?string $categoryName = null, ?string $kind = null): string
    {
        // Un plat ne devient jamais une bouteille
        if ($kind === 'food') {
            return 'plat';
        }

        foreach ([$name, (string) $categoryName] as $text) {
            $key = self::match($text);

            if ($key !== null) {
                // « Beaufort 65 cl », « Castel GM » : la grande bouteille
                if (str_starts_with($key, 'biere') && preg_match('/(?<![a-z0-9])(65|66|gm|grande?|1 ?l)(?![a-z0-9])/', self::normalize($name))) {
                    return 'grande';
                }

                return $key;
            }
        }

        return $kind === 'drink' ? 'boisson' : 'plat';
    }

    private static function match(string $text): ?string
    {
        $text = self::normalize($text);

        if ($text === '') {
            return null;
        }

        foreach (self::RULES as $key => $words) {
            if (preg_match('/(?<![a-z0-9])(' . $words . ')(?![a-z0-9])/', $text)) {
                return $key;
            }
        }

        return null;
    }

    private static function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }

    public static function color(string $key): string
    {
        return self::ICONS[$key][1] ?? self::ICONS['boisson'][1];
    }

    public static function shape(string $key): string
    {
        return self::ICONS[$key][2] ?? 'gobelet';
    }

    public static function label(string $key): string
    {
        return self::ICONS[$key][0] ?? '';
    }
}
