<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResetUserPassword extends Controller
{
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        // Cek OTP terlebih dahulu
        $otp = Otp::where('email', $request->email)->latest()->first();

        if (!$otp) {
            return $this->warning('OTP tidak ditemukan', 404);
        }

        // Cek apakah Email ada
        $user = User::where('email', $otp->email)->latest()->first();

        if (!$user) {
            return $this->warning('Email tidak ditemukan', 404);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();



        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return $this->success([],'Password berhasil diubah', 200);
    }
}
