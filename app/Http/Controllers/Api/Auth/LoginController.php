<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'password'  => 'required',
            'device_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('email', 'password');

        // GUNAKAN Auth default, bukan guard('api'), karena Sanctum tidak support attempt di custom guard
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah!'
            ], 401);
        }

        $user = Auth::user();

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;


        $response_data_user = [
            'id'        => $user->id,
            'nama'      => $user->nama,
            'nip'       => $user->nip,
            'pangkat'   => $user->pangkat,
            'jabatan'   => $user->jabatan,
            'instansi'  => $user->instansi,
            'email'     => $user->email,
            'no_tlp'    => $user->no_tlp,
            'domisili'  => $user->domisili,
            'role'      => $user->role,
            'photo'     => $user->photo ? asset('storage/photos/' . $user->photo) : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'user'    => $response_data_user,
            'token'   => $token
        ]);
    }
}
