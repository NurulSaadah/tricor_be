<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\ScreenAccessRoles;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StaffManagement;
use Illuminate\Support\Facades\DB;
use Validator;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid Credential', 'code' => '400'], 401);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            $id = User::select('id')->where('email', $request->email)->pluck('id');
            
            return response()->json(['message' => 'Invalid Crediential', "code" => 401], 401);

        }
        //get user ID
        $userInfo = User::select('id','staff_id','role_id','status')
        ->where('email', $request->email)->first();
        
        $detailInfo = StaffManagement::select('name','nric_no','reporting_manager_id','contact_no')
        ->where('staff_id',$userInfo->staff_id)
        ->first();

        $roleInfo = Roles::select('role_name')
        ->where('id', $userInfo->role_id)->first();

        // 0: active 1 : inactive
        if ($userInfo->status == 1) {
            return response()->json(['message' => 'User is Inactive', "code" => 202], 202);
        }

        $accessInfo=ScreenAccessRoles::select('id')->where('staff_id',$userInfo->id)->first();
        
        if($accessInfo == null){
            return response()->json(['message' => 'No Access has been assigned. Please Contact Your Adminitrator', "code" => 401], 401);
        }else{
                $screenroutealt = DB::table('screen_access_roles')
                ->select(DB::raw('screens.screen_route_alt'))
                ->join('screens', function ($join) {
                    $join->on('screens.id', '=', 'screen_access_roles.screen_id');
                })
                ->where('screen_access_roles.staff_id', '=', $userInfo->id)
                ->where('screen_access_roles.status', '=', '1')
                ->get();
       
            $route = json_decode(json_encode($screenroutealt[0]), true)['screen_route_alt'];
            return $this->createNewToken($token,$route,$detailInfo,$roleInfo);
        }
      
    }

        /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out'], 200);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token,$route,$detailInfo,$roleInfo)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 14400,
            'user' => auth()->user(),
            'detail'=> $detailInfo,
            'role'=> $roleInfo,
            'route_alt' => $route,
            'code' => '200'
        ]);
    }
            
        
    




}