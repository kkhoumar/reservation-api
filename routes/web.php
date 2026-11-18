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

// Choix du mot de passe (obligatoire après un mot de passe provisoire)
Route::get('/mot-de-passe', ChangePassword::class)->middleware('auth')->name('password.change');

// Page affichée au personnel d'un maquis verrouillé (non-paiement, essai terminé)
Route::view('/compte-suspendu', 'account-locked')->middleware('auth')->name('account.locked');

// Écrans d'un maquis : connexion + compte actif + maquis non verrouillé
Route::middleware(['auth', 'restaurant.access'])->group(function () {
    Route::get('/tables', TablesBoard::class)->name('tables.index');
    Route::get('/orders/new/{table?}', NewOrder::class)->name('orders.new');
    Route::get('/payment/{order}', TakePayment::class)->name('payment.take');
    Route::get('/payment/{order}/success', PaymentSuccess::class)->name('payment.success');

    Route::get('/dashboard', Dashboard::class)->middleware('role:owner,manager')->name('dashboard.index');
    Route::get('/cash-register', CashRegisterManage::class)->middleware('role:owner,manager,cashier')->name('cash-register.manage');
    Route::get('/cash-register/history', CashRegisterHistory::class)->middleware('role:owner,manager')->name('cash-register.history');
    Route::get('/cash-register/{register}/closed', CashRegisterClosed::class)->middleware('role:owner,manager,cashier')->name('cash-register.closed');
    Route::get('/comptoir', Counter::class)->middleware('role:owner,manager,cashier')->name('counter');
    Route::get('/credits', Ardoise::class)->middleware('role:owner,manager,cashier')->name('credits');
    Route::redirect('/ardoise', '/credits', 301);
    Route::get('/sacoches', Sacoches::class)->middleware('role:owner,manager,cashier')->name('sacoches');
    Route::get('/expenses', ExpensesList::class)->middleware('role:owner,manager,cashier')->name('expenses.index');
    Route::get('/sales-today', SalesToday::class)->middleware('role:owner,manager,cashier')->name('sales.today');
    Route::get('/users', UsersManage::class)->middleware('role:owner,manager')->name('users.manage');
    Route::get('/products', ProductsManage::class)->middleware('role:owner,manager')->name('products.manage');
    Route::get('/parametres', RestaurantSettings::class)->middleware('role:owner')->name('settings');
});

// Espace Super Admin (propriétaire de la plateforme)
Route::middleware(['auth', 'platform_admin'])->group(function () {
    Route::get('/admin/restaurants', RestaurantsList::class)->name('admin.restaurants');
    Route::get('/admin/restaurants/nouveau', CreateRestaurant::class)->name('admin.restaurants.create');

    // Sortir du maquis dans lequel le Super Admin est entré
    Route::post('/admin/quitter-maquis', function () {
        session()->forget(\App\Models\User::ADMIN_RESTAURANT_SESSION_KEY);

        return redirect()->route('admin.restaurants');
    })->name('admin.leave-restaurant');
});