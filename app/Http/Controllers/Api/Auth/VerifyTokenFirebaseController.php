<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\ExternalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerifyTokenFirebaseController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'device_id' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email wajib diisi!'
            ],422);
        }

        try {
            $user = User::where('email', $request->email)->first();
            if(!$user) {
                $external_user = ExternalUser::where('email', $request->email)->first();
                if(!$external_user) {
                    ExternalUser::create([
                        'name'  => $request->name,
                        'email' => $request->email,
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak terdaftar!'
                ], 404);
            }
            
            //simpan user_id dan device_id ke table user device
            $user_device = UserDevice::where('user_id', $user->id)->first();
            if(!$user_device) {
                UserDevice::create([
                    'user_id'   => $user->id,
                    'device_id' => $request->device_id
                ]);
            }

            $token = JWTAuth::fromUser($user);
            $respone_data_user = [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
                'role'  => $user->role,
                'opd'   => [
                    'nama_opd'  => $user->opd->nama_opd,
                    'lat'       => $user->opd->lat,
                    'long'      => $user->opd->long,
                ]
            ];
            return response()->json([
                'success'       => true,
                'user'          => $respone_data_user,
                'permissions'   => $user->getPermissionArray(),
                'token'         => $token
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
