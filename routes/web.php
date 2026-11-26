<?php

use App\Livewire\Admin\CreateRestaurant;
use App\Livewire\Admin\RestaurantsList;
use App\Livewire\ChangePassword;
use App\Livewire\CashRegisterClosed;
use App\Livewire\CashRegisterHistory;
use App\Livewire\CashRegisterManage;
use App\Livewire\Ardoise;
use App\Livewire\Counter;
use App\Livewire\Dashboard;
use App\Livewire\ExpensesList;
use App\Livewire\Landing;
use App\Livewire\Login;
use App\Livewire\NewOrder;
use App\Livewire\PaymentSuccess;
use App\Livewire\DemoRequestForm;
use App\Livewire\RestaurantSettings;
use App\Livewire\Sacoches;
use App\Livewire\SalesToday;
use App\Livewire\TablesBoard;
use App\Livewire\TakePayment;
use App\Livewire\UsersManage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\ProductsManage;

Route::get('/', Landing::class)->middleware('guest')->name('landing');
// Plus d'inscription libre : c'est le Super Admin qui crée les maquis
Route::get('/demande-demo', DemoRequestForm::class)->middleware('guest')->name('demo.request');
Route::redirect('/register', '/demande-demo', 301);
Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Point d'entrée de l'application (icône installée sur le téléphone, liens
// génériques) : chacun arrive sur le bon écran selon son rôle
Route::get('/app', function () {
    $user = auth()->user();

    return match (true) {
        ! $user => redirect()->route('login'),
        $user->isPlatformAdmin() && ! $user->currentRestaurant() => redirect()->route('admin.restaurants'),
        $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true) => redirect()->route('dashboard.index'),
        default => redirect()->route('tables.index'),
    };
})->name('app.start');

// Carte d'identité de l'application installable (PWA)
Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => 'Maquis Express',
        'short_name' => 'Maquis Express',
        'description' => 'Commandes, comptoir, caisse, stock et crédits clients pour votre maquis.',
        'id' => '/app',
        'start_url' => '/app',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#0B4D3C',
        'theme_color' => '#0B4D3C',
        'lang' => 'fr',
        'dir' => 'ltr',
        'categories' => ['business', 'productivity', 'food'],
        'icons' => [
            ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            ['name' => 'Prendre une commande', 'url' => '/tables', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ['name' => 'Comptoir', 'url' => '/comptoir', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ['name' => 'Caisse', 'url' => '/cash-register', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->name('pwa.manifest');

// Documents légaux (publics) et acceptation par le patron
Route::view('/conditions', 'legal.terms')->name('legal.terms');
Route::view('/confidentialite', 'legal.privacy')->name('legal.privacy');
Route::get('/conditions/accepter', \App\Livewire\AcceptTerms::class)->middleware('auth')->name('terms.accept');

// Choix du mot de passe (obligatoire après un mot de passe provisoire)
Route::get('/mot-de-passe', ChangePassword::class)->middleware('auth')->name('password.change');

// Mon profil (tous les comptes) et photos de profil, visibles seulement par le même maquis
Route::get('/mon-profil', \App\Livewire\Profile::class)->middleware('auth')->name('profile');
Route::get('/photo/{user}', function (\App\Models\User $user) {
    abort_unless(auth()->user()->canSeeAvatarOf($user) && $user->avatar_path, 404);

    return \App\Support\PhotoStore::response($user->avatar_path);
})->middleware('auth')->name('avatar.show');

// Page affichée au personnel d'un maquis verrouillé (non-paiement, essai terminé)
Route::view('/compte-suspendu', 'account-locked')->middleware('auth')->name('account.locked');

// Écrans d'un maquis : connexion + compte actif + maquis non verrouillé
Route::middleware(['auth', 'restaurant.access'])->group(function () {
    Route::get('/tables', TablesBoard::class)->name('tables.index');
    Route::get('/tables/gerer', \App\Livewire\TablesManage::class)->middleware('role:owner,manager')->name('tables.manage');
    Route::get('/orders/new/{table?}', NewOrder::class)->name('orders.new');
    Route::get('/payment/{order}', TakePayment::class)->name('payment.take');
    Route::get('/payment/{order}/success', PaymentSuccess::class)->name('payment.success');

    Route::get('/dashboard', Dashboard::class)->middleware('role:owner,manager,associate')->name('dashboard.index');
    Route::get('/cash-register', CashRegisterManage::class)->middleware('role:owner,manager,cashier')->name('cash-register.manage');
    Route::get('/cash-register/history', CashRegisterHistory::class)->middleware('role:owner,manager,associate')->name('cash-register.history');
    Route::get('/cash-register/{register}/closed', CashRegisterClosed::class)->middleware('role:owner,manager,cashier,associate')->name('cash-register.closed');
    Route::get('/comptoir', Counter::class)->middleware('role:owner,manager,cashier')->name('counter');

    // Vente de secours (sans internet) : page gardée dans le téléphone, catalogue, envoi des ventes
    Route::middleware('role:owner,manager,cashier')->group(function () {
        Route::get('/secours', [\App\Http\Controllers\OfflineSaleController::class, 'page'])->name('offline.page');
        Route::get('/hors-ligne/catalogue', [\App\Http\Controllers\OfflineSaleController::class, 'catalogue'])->name('offline.catalogue');
        Route::post('/hors-ligne/ventes', [\App\Http\Controllers\OfflineSaleController::class, 'sync'])->middleware('throttle:30,1')->name('offline.sync');
    });
    Route::get('/credits', Ardoise::class)->middleware('role:owner,manager,cashier')->name('credits');
    Route::redirect('/ardoise', '/credits', 301);
    Route::get('/sacoches', Sacoches::class)->middleware('role:owner,manager,cashier')->name('sacoches');
    Route::get('/expenses', ExpensesList::class)->middleware('role:owner,manager,cashier')->name('expenses.index');
    Route::get('/sales-today', SalesToday::class)->middleware('role:owner,manager,cashier,associate')->name('sales.today');
    Route::get('/users', UsersManage::class)->middleware('role:owner,manager')->name('users.manage');
    Route::get('/products', ProductsManage::class)->middleware('role:owner,manager')->name('products.manage');
    // Photo d'un produit (prise par le patron) : visible par toute l'équipe du maquis, pas par les autres
    Route::get('/produit/photo/{product}', function (\App\Models\Product $product) {
        abort_unless($product->photo, 404);

        return \App\Support\PhotoStore::response($product->photo);
    })->name('products.photo');
    Route::get('/stock', \App\Livewire\StockManage::class)->middleware('role:owner,manager,associate')->name('stock.index');
    Route::get('/stock/photo/{movement}', function (\App\Models\StockMovement $movement) {
        abort_unless(\App\Actions\StockService::canView(auth()->user()) && $movement->photo_path, 404);

        return \App\Support\PhotoStore::response($movement->photo_path);
    })->middleware('role:owner,manager,associate')->name('stock.photo');
    Route::get('/depenses/recu/{expense}', function (\App\Models\Expense $expense) {
        $user = auth()->user();
        $canSee = $user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager', 'associate'], true) || $expense->user_id === $user->id;
        abort_unless($canSee && $expense->receipt_path, 404);

        return \App\Support\PhotoStore::response($expense->receipt_path);
    })->name('expenses.receipt');
    Route::get('/journal', \App\Livewire\ActivityJournal::class)->middleware('role:owner,manager,associate')->name('journal');
    Route::get('/comptage',\App\Livewire\StockCountPage::class)->middleware('role:owner,manager,associate')->name('stock.count');
    Route::get('/comptage/{stockCount}', \App\Livewire\StockCountShow::class)->middleware('role:owner,manager,associate')->name('stock.count.show');
    Route::get('/exports', [\App\Http\Controllers\ExportController::class, 'index'])->middleware('role:owner,manager,associate')->name('exports.index');
    Route::get('/exports/{type}', [\App\Http\Controllers\ExportController::class, 'download'])->middleware('role:owner,manager,associate')->name('exports.download');
    Route::get('/parametres', RestaurantSettings::class)->middleware('role:owner')->name('settings');
});

// Espace Super Admin (propriétaire de la plateforme)
Route::middleware(['auth', 'platform_admin'])->group(function () {
    Route::get('/admin/restaurants', RestaurantsList::class)->name('admin.restaurants');
    Route::get('/admin/restaurants/nouveau', CreateRestaurant::class)->name('admin.restaurants.create');
    Route::get('/admin/restaurants/{restaurant}/abonnement', \App\Livewire\Admin\RestaurantSubscription::class)->name('admin.restaurants.subscription');
    Route::get('/admin/formules', \App\Livewire\Admin\PlansManage::class)->name('admin.plans');
    Route::get('/admin/abonnes', \App\Livewire\Admin\Subscribers::class)->name('admin.subscribers');
    Route::get('/admin/reglages', \App\Livewire\Admin\PlatformSettings::class)->name('admin.settings');

    // Sortir du maquis dans lequel le Super Admin est entré
    Route::post('/admin/quitter-maquis', function () {
        session()->forget(\App\Models\User::ADMIN_RESTAURANT_SESSION_KEY);

        return redirect()->route('admin.restaurants');
    })->name('admin.leave-restaurant');
});
