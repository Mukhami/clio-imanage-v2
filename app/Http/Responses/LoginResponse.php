<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();

        $redirect = match (true) {
            $user->hasAnyRole(['Super Admin', 'Admin', 'Support']) => route('admin.dashboard'),
            $user->hasAnyRole(['Tenant Admin', 'Tenant Viewer'])   => route('portal.dashboard'),
            default                                                 => route('admin.dashboard'),
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirect);
    }
}
