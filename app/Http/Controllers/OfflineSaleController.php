<?php

namespace App\Http\Controllers;

use App\Actions\RecordOfflineSale;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vente de secours (sans internet) :
 * - la page /secours, gardée dans le téléphone, marche sans connexion ;
 * - le catalogue (produits, prix) est copié dans le téléphone quand internet marche ;
 * - les ventes gardées sont envoyées ici dès que la connexion revient.
 */
class OfflineSaleController extends Controller
{
    public function page()
    {
        abort_unless(RecordOfflineSale::canUse(auth()->user()), 403);

        return response()
            ->view('offline-sale')
            ->header('Cache-Control', 'no-cache');
    }

    public function catalogue(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(RecordOfflineSale::canUse($user), 403);

        $products = Product::with('category')
            ->where('is_available', true)->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'selling_price', 'stock_quantity', 'track_stock', 'sold_out_on', 'category_id'])
            ->reject(fn (Product $p) => $p->isSoldOutToday());

        $categories = $products->groupBy(fn ($p) => $p->category?->name ?? 'Autres')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'kind' => $group->first()->category?->kind ?? Category::guessKind($name),
                'products' => $group->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (int) $p->selling_price,
                    // Le dessin du produit (gardé dans la page, il marche sans internet)
                    'shape' => \App\Support\ProductIcon::shape($key = $p->iconKey($p->category)),
                    'color' => \App\Support\ProductIcon::color($key),
                    'stock' => $p->track_stock ? (int) $p->stock_quantity : null,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'restaurant' => ['id' => $user->currentRestaurantId(), 'name' => $user->restaurant?->name],
            'user' => ['id' => $user->id, 'name' => $user->first_name],
            'register_open' => CashRegister::isOpenFor($user->currentRestaurantId()),
            'csrf' => csrf_token(),
            'generated_at' => now()->toIso8601String(),
            'categories' => $categories,
        ])->header('Cache-Control', 'no-store');
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(RecordOfflineSale::canUse($user), 403);

        $sales = $request->input('sales');

        if (! is_array($sales) || $sales === [] || count($sales) > 50) {
            return response()->json(['message' => 'Envoi invalide.'], 422);
        }

        $results = [];

        foreach ($sales as $sale) {
            $results[] = RecordOfflineSale::handle($user, is_array($sale) ? $sale : []);
        }

        return response()->json(['results' => $results])->header('Cache-Control', 'no-store');
    }
}
