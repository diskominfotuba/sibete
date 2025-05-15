<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class ValidateTokenController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        try {
            if ($user = !JWTAuth::parseToken()->authenticate()) {
                return response()->json(['valid' => false], 401);
            }
    
            $user = auth()->guard('api')->user();
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
                'valid' => true,
                'user'  =>$respone_data_user
            ], 200);
        } catch (Exception $e) {
            return response()->json(['valid' => false, 'error' => $e->getMessage()], 401);
        }
    }
}
