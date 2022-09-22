<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmployerRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store_emp(EmployerRequest $request)
    {
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
        auth()->guard('emp')->user()->tokens()->delete();
        $token = auth()->guard('emp')->user()->createToken(Str::random(16));

        return response()->json(['token' => $token->plainTextToken]);
    }

    public function store(LoginRequest $request)
    {
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }
        $request->user()->tokens()->delete();
        $token = $request->user()->createToken(Str::random(16));

        return response()->json(['token' => $token->plainTextToken]);

//        $request->session()->regenerate();
//
//        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
