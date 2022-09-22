<?php

use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('/');
//
//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth'])->name('dashboard');

//Route::get('/parsing', [MainController::class,'parsing']);
//Route::get('/vacancies/{page}', [MainController::class,'vacancies']);
//Route::get('/vacancies', [MainController::class,'vacancies'])->name('vacancy');
//Route::get('/vacancy/new', [MainController::class, 'new'])->name('new');

require __DIR__.'/auth.php';
