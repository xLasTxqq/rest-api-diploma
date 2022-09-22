<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredEmployerController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\SummariesController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\VacancyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::match(['get','post'],'/vacancy/{id?}', [VacancyController::class, 'index']);//Получение вакансий(и)
Route::get('/filters', [FilterController::class, 'index']);//Фильтры
Route::post('/search', [FilterController::class, 'search']);//Поиск в фильтрах
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/register/employer', [RegisteredEmployerController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/login/employer', [AuthenticatedSessionController::class, 'store_emp']);

Route::middleware('auth:sanctum')->group(function (){
    Route::post('/create/vacancy', [VacancyController::class, 'store']);//Создать|Обновить вакансию
    Route::get('/delete/vacancy/{id?}',[VacancyController::class, 'destroy']);//Удалить вакансию(и)
    Route::post('/summaries/{id?}',[SummariesController::class, 'edit']);//Изменить статус резюме
    Route::get('/summaries',[SummariesController::class,'index']);//Вывод отправленных резюме пользователю
    Route::get('/summaries/delete/{id?}',[SummariesController::class,'destroy']);//Удаление резюме
    Route::post('/summary/{id}', [SummaryController::class, 'create']);//Отправка резюме
    Route::get('/summary',[SummaryController::class,'index']);//Вывод резюме
    Route::post('/summary', [SummaryController::class,'store']);//Создание|Изменение резюме
    Route::get('/vacancies', [VacancyController::class, 'indexEmployer']);//Вывод вакансий с резюме работодателю
});
//Route::get('/vacancy/{id?}', [MainController::class, 'vac'])->name('vac');
//search for filters
//Route::post('/vacancies', [MainController::class,'vac']);

//Route::middleware('auth:sanctum')->group(function (){
//    Route::post('/vacancy/new', [MainController::class, 'new'])->name('new');//Создать вакансию
//    Route::get('/vacancy/all', [MainController::class, 'vacancy_all']);//Создать вакансию
//    Route::get('/vacancy/delete/{vacancy?}', [MainController::class, 'vacancy_delete'])->name('vacancy_delete');//Удалить вакансию
//    Route::post('/vacancy/change/{summary}', [MainController::class, 'vacancy_change']);//За работодателя отклонить или принять заявку
//    Route::get('/summaries/user/all', [MainController::class, 'summary_user_all']);//Вывод всех заявок пользователю
//    Route::get('/summaries/user/delete/{id}', [MainController::class, 'summary_user_delete']);//Удалить заявку на вакансию
//    Route::get('/summaries/user/delete', [MainController::class, 'summary_user_delete']);//Удалить все заявки на вакансии
//    Route::post('/summaries/user', [MainController::class, 'summary_user']);//Вывод резюме
//    Route::post('/summaries/create', [MainController::class, 'summary_create']);//Создание резюме пользователя
//    Route::get('/summaries', [MainController::class, 'summaries_vacancy']);// Вывод работодателю все заявки
//    Route::get('/summaries/{vacancy}', [MainController::class, 'summaries_vacancy']);//Вывод работодателю заявки на вакансию
//    Route::get('/summaries/send/{vacancy}', [MainController::class, 'summary_send']);//Отправить заявку работодателю на вакансию
//});


