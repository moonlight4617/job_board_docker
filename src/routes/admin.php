<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Admin\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Admin\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\CompaniesController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\TagsController;
use App\Http\Controllers\Admin\JobsController;
use App\Http\Controllers\Admin\MessageController;


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

// Route::get('/', function () {
//   return view('admin.welcome');
// });

// 企業検索
Route::get('companies/query', [CompaniesController::class, 'query'])->middleware('auth:admin')->name('companies.query');
// 企業登録、一覧、詳細、削除
Route::resource('companies', CompaniesController::class)->middleware('auth:admin');
// ユーザー検索
Route::get('users/query', [UsersController::class, 'query'])->middleware('auth:admin')->name('users.query');
// ユーザー登録、一覧、詳細、削除
Route::resource('users', UsersController::class)->middleware('auth:admin');

// タグ関連
Route::get('/tags', [TagsController::class, 'index'])->middleware(['auth:admin'])->name('tags.index');
Route::post('/tags/store', [TagsController::class, 'store'])->middleware(['auth:admin'])->name('tags.store');
Route::delete('/tags/destroy', [TagsController::class, 'destroy'])->middleware(['auth:admin'])->name('tags.destroy');

// 求人関連
Route::get('/jobs', [JobsController::class, 'index'])->middleware(['auth:admin'])->name('jobs.index');
Route::get('/jobs/query', [JobsController::class, 'query'])->middleware(['auth:admin'])->name('jobs.query');
Route::get('/jobs/{job}', [JobsController::class, 'show'])->middleware(['auth:admin'])->name('jobs.show');

// メッセージ関連
Route::get('/user/messagesIndex/{user}', [MessageController::class, 'userMessageIndex'])->middleware(['auth:admin'])->name('users.messageIndex');
Route::get('/user/messages/{user}', [MessageController::class, 'show'])->middleware(['auth:admin'])->name('users.messageShow');
Route::delete('/user/messages/{user}', [MessageController::class, 'delete'])->middleware(['auth:admin'])->name('users.messageDelete');


// Route::get('/', function () {
//   return view('admin.dashboard');
// })->middleware(['auth:admin'])->name('dashboard');

Route::get('/register', [RegisteredUserController::class, 'create'])
  ->middleware('guest')
  ->name('register');

Route::post('/register', [RegisteredUserController::class, 'store'])
  ->middleware('guest');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
  ->middleware('guest')
  ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
  ->middleware('guest');

Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
  ->middleware('guest')
  ->name('password.request');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
  ->middleware('guest')
  ->name('password.email');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
  ->middleware('guest')
  ->name('password.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
  ->middleware('guest')
  ->name('password.update');

Route::get('/verify-email', [EmailVerificationPromptController::class, '__invoke'])
  ->middleware('auth:admin')
  ->name('verification.notice');

Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
  ->middleware(['auth:admin', 'signed', 'throttle:6,1'])
  ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
  ->middleware(['auth:admin', 'throttle:6,1'])
  ->name('verification.send');

Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
  ->middleware('auth:admin')
  ->name('password.confirm');

Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])
  ->middleware('auth:admin');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
  ->middleware('auth:admin')
  ->name('logout');
