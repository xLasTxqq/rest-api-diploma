<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisteredEmployerController extends Controller
{

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'surname' => ['required', 'string', 'max:255'],
                'company' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:employers'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $employer = Employer::create([
                'name' => $request->name,
                'surname' => $request->surname,
                'email' => $request->email,
                'company' => $request->company,
                'password' => Hash::make($request->password),
            ]);

            event(new Registered($employer));

            $token = $employer->createToken(Str::random(16));

            return response()->json(['token' => $token->plainTextToken]);
        } catch (Exception $exception) {
            return response()->json(['errors' => $exception->getMessage()]);
        }

    }
}
