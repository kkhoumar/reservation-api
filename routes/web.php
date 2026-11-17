<?php

use App\Livewire\Admin\RestaurantsList;
use App\Livewire\CashRegisterClosed;
use App\Livewire\CashRegisterHistory;
use App\Livewire\CashRegisterManage;
use App\Livewire\Dashboard;
use App\Livewire\ExpensesList;
use App\Livewire\Landing;
use App\Livewire\Login;
use App\Livewire\NewOrder;
use App\Livewire\PaymentSuccess;
use App\Livewire\Register;
use App\Livewire\SalesToday;
use App\Livewire\TablesBoard;
use App\Livewire\TakePayment;
use App\Livewire\UsersManage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\ProductsManage;

Route::get('/', Landing::class)->middleware('guest')->name('landing');
Route::get('/register', Register::class)->middleware('guest')->name('register');
Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/tables', TablesBoard::class)->name('tables.index');
    Route::get('/orders/new/{table?}', NewOrder::class)->name('orders.new');
    Route::get('/payment/{order}', TakePayment::class)->name('payment.take');
    Route::get('/payment/{order}/success', PaymentSuccess::class)->name('payment.success');

    Route::get('/dashboard', Dashboard::class)->middleware('role:owner,manager')->name('dashboard.index');
    Route::get('/cash-register', CashRegisterManage::class)->middleware('role:owner,manager,cashier')->name('cash-register.manage');
    Route::get('/cash-register/history', CashRegisterHistory::class)->middleware('role:owner,manager')->name('cash-register.history');
    Route::get('/cash-register/{register}/closed', CashRegisterClosed::class)->middleware('role:owner,manager,cashier')->name('cash-register.closed');
    Route::get('/expenses', ExpensesList::class)->middleware('role:owner,manager,cashier')->name('expenses.index');
    Route::get('/sales-today', SalesToday::class)->middleware('role:owner,manager,cashier')->name('sales.today');
    Route::get('/users', UsersManage::class)->middleware('role:owner,manager')->name('users.manage');
    Route::get('/products', ProductsManage::class)->middleware('role:owner,manager')->name('products.manage');
});

Route::middleware(['auth', 'platform_admin'])->group(function () {
    Route::get('/admin/restaurants', RestaurantsList::class)->name('admin.restaurants');
});