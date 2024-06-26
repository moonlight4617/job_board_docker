<?php

declare(strict_types=1);

namespace App\Http\Controllers\Companies\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

final class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::COMPANY_HOME . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // ここを企業編集画面に遷移させるようにしておけばメール確認後に編集画面へ？
        return redirect()->intended(RouteServiceProvider::COMPANY_HOME . '?verified=1');
    }
}
