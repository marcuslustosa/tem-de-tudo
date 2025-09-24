<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'cliente', // Padrão para programa de fidelidade
            'pontos' => 100, // Bônus de boas-vindas
            'telefone' => $request->phone,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário criado com sucesso! Você ganhou 100 pontos de boas-vindas!',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        // Calcular nível baseado nos pontos
        $nivel = 'Bronze';
        if ($user->pontos >= 10000) {
            $nivel = 'Diamante';
        } elseif ($user->pontos >= 5000) {
            $nivel = 'Ouro';
        } elseif ($user->pontos >= 1000) {
            $nivel = 'Prata';
        }
        
        return response()->json([
            'user' => array_merge($user->toArray(), ['nivel' => $nivel])
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }
    
    public function addPontos(Request $request)
    {
        $request->validate([
            'pontos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:255',
        ]);
        
        $user = $request->user();
        $user->pontos += $request->pontos;
        $user->save();
        
        return response()->json([
            'message' => "Você ganhou {$request->pontos} pontos!",
            'pontos_total' => $user->pontos,
            'nivel' => $this->calculateLevel($user->pontos)
        ]);
    }
    
    private function calculateLevel($pontos)
    {
        if ($pontos >= 10000) return 'Diamante';
        if ($pontos >= 5000) return 'Ouro';
        if ($pontos >= 1000) return 'Prata';
        return 'Bronze';
    }
}
