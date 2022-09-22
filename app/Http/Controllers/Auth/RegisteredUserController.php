<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{

    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        try{
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $token=$user->createToken(Str::random(16));

        return response()->json(['token'=>$token->plainTextToken]);
        }
        catch(Exception $exception){
            return response()->json(['errors' => $exception->getMessage()]);
        }

//        dd($token);

//        Laravel\Sanctum\NewAccessToken::class->createToken();

//        Auth::login($user);
//
//        return redirect(RouteServiceProvider::HOME);
    }
}
