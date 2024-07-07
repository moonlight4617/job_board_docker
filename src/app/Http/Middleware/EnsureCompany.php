<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Companies;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class EnsureCompany
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!($request->route() instanceof \Illuminate\Routing\Route)) return $next($request);

        $id = $request->route()->parameter('company');
        if (!is_null($id)) {
            $companyId = Companies::findOrFail($id)->id;
            if ($companyId !== Auth::id()) {
                abort(404); // 404画面表示 }
            }

            return $next($request);
        }

        return $next($request);
    }
}
