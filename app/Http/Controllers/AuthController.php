<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\ValidateLoginCredintialRequest;

class AuthController extends Controller
{
    public function signUp(StoreUserRequest $request)
    {
        try {
            $user = User::create($request->validated());

            return response()->json(
                [
                    'status' => true,
                    'message' => 'User Registered successfully',
                    'data' => $user
                ],
                201
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User Creation failed',
                    'errors' => [$e->getMessage()]
                ],
                500
            );
        }
    }

    public function login(ValidateLoginCredintialRequest $request)
    {
        try {
            $user = User::where('email', $request->validated()['email'])->first();
            if (!$user || !Hash::check($request->validated()['password'], $user->password)) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Invalid email or password',
                        'errors' => []
                    ],
                    401
                );
            }
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(
                [
                    'status' => true,
                    'message' => 'User logged in successfully',
                    'data' => ['token' => $token, 'user' => $user],
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'User login failed',
                    'errors' => [$e->getMessage()]
                ],
                500
            );
        }
    }

        public function me()
    {
        try {
            $user = Auth::user();
            
            return response()->json(
                [
                    'status' => true,
                    'message' => 'Authenticated User',
                    'data' => ['user' => $user],
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'error',
                ],
                500
            );
        }
    }
}