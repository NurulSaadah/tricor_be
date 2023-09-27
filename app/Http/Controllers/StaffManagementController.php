<?php

namespace App\Http\Controllers;
use App\Mail\UpdateEmail;
use Illuminate\Http\Request;
use App\Models\StaffManagement;
use Exception;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Support\Facades\Mail;
use App\Models\ScreenAccessRoles;

class StaffManagementController extends Controller
{

    public function getStaffList()
    {
        $userList = DB::table('staff_management')
        ->select('staff_management.staff_id','staff_management.name','staff_management.nric_no','staff_management.contact_no','users.email','manager.name as manager','users.status as status','roles.role_name as role','users.id as user_id')
        ->leftJoin('users','users.staff_id','=','staff_management.staff_id')
        ->leftJoin('Roles','roles.id','=','users.role_id')
        ->leftJoin('staff_management as manager','manager.staff_id','=','staff_management.reporting_manager_id')
        ->orderBy('staff_management.name','asc')
        ->get();

        foreach($userList as $item){
        
            $item->name  =  strtoupper($item->name) ?? '-';
            $item->nric_no = $item->nric_no ?? '-';
            $item->contact_no = $item->contact_no ?? '-';
            $item->email = $item->email ?? '-';
            $item->manager = strtoupper($item->manager) ?? '-';
            $item->role = strtoupper($item->role) ?? '-';
            
            if($item->status == 0){
                $item->status = 'Active'; 
            }
            if($item->status == 1){
                $item->status = 'Inactive'; 
            }
            
        }
        return response()->json(["message" => "Staff List", 'list' => $userList, "code" => 200]);
    }
    public function getStaffListbyCode($code)
    {
        $userList = DB::table('staff_management')
        ->select('staff_management.staff_id','staff_management.name','staff_management.nric_no','staff_management.contact_no','users.email','manager.name as manager','users.status as status','roles.role_name as role')
        ->leftJoin('users','users.staff_id','=','staff_management.staff_id')
        ->leftJoin('Roles','roles.id','=','users.role_id')
        ->leftJoin('staff_management as manager','manager.staff_id','=','staff_management.reporting_manager_id')
        ->where('roles.code',$code)
        ->orderBy('staff_management.name','asc')
        ->get();
        
        foreach($userList as $item){
        
            $item->name  =  strtoupper($item->name) ?? '-';
            $item->nric_no = $item->nric_no ?? '-';
            $item->contact_no = $item->contact_no ?? '-';
            $item->email = $item->email ?? '-';
            $item->manager = strtoupper($item->manager) ?? '-';
            $item->role = strtoupper($item->role) ?? '-';
            
            if($item->status == 0){
                $item->status = 'Active'; 
            }
            if($item->status == 1){
                $item->status = 'Inactive'; 
            }
            
        }
       
        return response()->json(["message" => "Staff List by code :", 'list' => $userList, "code" => 200]);
    }
    public function getStaffListbyId(Request $request)
    {
        $userList = DB::table('staff_management')
        ->select('staff_management.staff_id','staff_management.name','staff_management.nric_no','staff_management.contact_no','users.email','manager.staff_id as manager_id','manager.name as manager_name','users.status as status','roles.id as role_id','roles.role_name as role')
        ->leftJoin('users','users.staff_id','=','staff_management.staff_id')
        ->leftJoin('Roles','roles.id','=','users.role_id')
        ->leftJoin('staff_management as manager','manager.staff_id','=','staff_management.reporting_manager_id')
        ->where('staff_management.staff_id',$request->staff_id)
        ->first();
        
       
        return response()->json(["message" => "Staff List by ID :", 'list' => $userList, "code" => 200]);
    }
    public function createNewStaff(Request $request)
    {
      
        $dataStaff = [
            'name' => $request->name,
            'nric_no' => $request->nric_no,
            'contact_no' => $request->contact_no,
            'reporting_manager_id' => $request->reporting_manager_id,
        ];

        if($request->editId ==''){
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'nric_no' => 'required',
                'email' => 'required|string|unique:users',
                'contact_no' => 'required',
                'role_id' => 'required',
                'added_by' => 'required',
            ]);
            if ($validator->fails()) { return response()->json(["message" => $validator->errors(), "code" => 422]); }

          //1.create staff 
          $createStaff = StaffManagement::create($dataStaff);

          $dataUser = [
            'staff_id' => $createStaff->getKey(),
            'email' => $request->email,
            'role_id' => $request->role_id,
            'status' => $request->status,
            'password' => bcrypt($request->email)
        ];
          //2. create user
          $createUser = User::create($dataUser);

          //3. create default Role Access Page
          $defaultRoleAccess = DB::table('default_role_access')
          ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
          ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
          ->where('default_role_access.role_id', $request->role_id)
          ->get();

            if ($defaultRoleAccess) {
                foreach ($defaultRoleAccess as $key) {
                    $screen = [
                        'module_id' => $key->module_id,
                        'sub_module_id' => $key->sub_module_id,
                        'screen_id' => $key->screen_id,
                        'staff_id' => $createUser->getKey(),
                        'access_screen' => '1',

                    ];

                    if (ScreenAccessRoles::where($screen)->count() == 0) {
                        $screen['added_by'] = $request->added_by;
                        ScreenAccessRoles::Create($screen);
                    }
                }

                return response()->json(["message" => "Record Successfully Created", "code" => 200]);
            }

        }else{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'nric_no' => 'required',
                'email' => 'required|string',
                'contact_no' => 'required',
                'role_id' => 'required',
                'added_by' => 'required',
            ]);
            if ($validator->fails()) { return response()->json(["message" => $validator->errors(), "code" => 422]); }
            // edit existing record
           
            $updateStaff = StaffManagement::where('staff_id',$request->editId)->update($dataStaff); 
            $dataUpdateUser = [
                'email' => $request->email,
                'role_id' => $request->role_id,
                'status' => $request->status,
            ];
            $res = User::where('staff_id',$request->editId)->update($dataUpdateUser);
            $getUserID = User::select('id')->where('staff_id',$request->editId)->first();
            $deleteAccessScreen = DB::table('screen_access_roles')->where('staff_id',$getUserID->id)
            ->delete(); // staff id in this table refer to id in users.

            
            $defaultRoleAccess = DB::table('default_role_access')
            ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
            ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
            ->where('default_role_access.role_id', $request->role_id)
            ->get();
            
              if ($defaultRoleAccess) {

                  foreach ($defaultRoleAccess as $key) {
                      $screen = [
                          'module_id' => $key->module_id,
                          'sub_module_id' => $key->sub_module_id,
                          'screen_id' => $key->screen_id,
                          'staff_id' => $getUserID->id ,// ni ambik id dari table user
                          'access_screen' => '1',
  
                      ];
  
                      if (ScreenAccessRoles::where($screen)->count() == 0) {
                          $screen['added_by'] = $request->added_by;
                          ScreenAccessRoles::Create($screen);
                      }
                  }
                  return response()->json(["message" => "Record Successfully Updated", "code" => 200]);
              }
            
          
        }

      

    }
    public function isExistNric(Request $request)
    {
        $check = StaffManagement::where('nric_no', $request->nric_no)->count();
        if ($check == 0) {
            return response()->json(["message" => "Staff Management List", 'list' => "Not Exist", "code" => 400]);
        } else {
            return response()->json(["message" => "Staff Management List", 'list' => "Exist", "code" => 200]);
        }
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
  

    public function getStaffManagementDetailsById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        $role = StaffManagement::where('staff_management.id', $request->id)->first();

        if ($role->role_id != 0) {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select('staff_management.id as Staff_managementId', 'staff_management.name', 'staff_management.nric_no', 'general_setting.section_value as designation_name', 'staff_management.designation_period_start_date', 'staff_management.designation_period_end_date', 'staff_management.registration_no', 'roles.role_name', 'service_register.service_name', 'staff_management.branch_id', 'staff_management.is_incharge', 'staff_management.contact_no', 'staff_management.email', 'staff_management.status', 'staff_management.start_date', 'staff_management.end_date', 'hospital_branch__details.hospital_branch_name')
                ->where('staff_management.id', '=', $request->id)
                ->get();
        } else if ($role->role_id == 0) {

            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select(
                    'staff_management.id as Staff_managementId',
                    'staff_management.name',
                    'staff_management.nric_no',
                    'general_setting.section_value as designation_name',
                    'staff_management.designation_period_start_date',
                    'staff_management.designation_period_end_date',
                    'staff_management.registration_no',
                    'service_register.service_name',
                    'staff_management.branch_id',
                    'staff_management.is_incharge',
                    'staff_management.contact_no',
                    'staff_management.email',
                    'staff_management.status',
                    'staff_management.start_date',
                    'staff_management.end_date',
                    'hospital_branch__details.hospital_branch_name'
                )
                ->where('staff_management.id', '=', $request->id)
                ->get();
        }
        return response()->json(["message" => "Staff Management Details", 'list' => $users, "code" => 200]);
    }

    public function editStaffManagementDetailsById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        $role = StaffManagement::where('id', $request->id)->first();

        if ($role->role_id != 0) {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select(
                    'staff_management.id as Staff_managementId',
                    'staff_management.name',
                    'staff_management.role_id',
                    'staff_management.team_id',
                    'staff_management.nric_no',
                    'staff_management.branch_id',
                    'general_setting.section_value as designation_name',
                    'general_setting.id as designation_id',
                    'staff_management.designation_period_start_date',
                    'staff_management.designation_period_end_date',
                    'staff_management.registration_no',
                    'roles.code',
                    'roles.role_name',
                    'service_register.service_name',
                    'hospital_branch__details.hospital_branch_name',
                    'staff_management.branch_id',
                    'staff_management.is_incharge',
                    'staff_management.contact_no',
                    'staff_management.email',
                    'staff_management.status',
                    'staff_management.start_date',
                    'staff_management.end_date'
                )
                ->where('staff_management.id', '=', $request->id)
                ->get();
        } else if ($role->role_id == 0) {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select(
                    'staff_management.id as Staff_managementId',
                    'staff_management.name',
                    'staff_management.role_id',
                    'staff_management.team_id',
                    'staff_management.nric_no',
                    'staff_management.branch_id',
                    'general_setting.section_value as designation_name',
                    'general_setting.id as designation_id',
                    'staff_management.designation_period_start_date',
                    'staff_management.designation_period_end_date',
                    'staff_management.registration_no',
                    'service_register.service_name',
                    'hospital_branch__details.hospital_branch_name',
                    'staff_management.branch_id',
                    'staff_management.is_incharge',
                    'staff_management.contact_no',
                    'staff_management.email',
                    'staff_management.status',
                    'staff_management.start_date',
                    'staff_management.end_date'
                )
                ->where('staff_management.id', '=', $request->id)
                ->get();
        }
        return response()->json(["message" => "Staff Management Details", 'list' => $users, "code" => 200]);
    }

    public function updateStaffManagement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'name' => 'required|string',
            'id' => 'required|integer',
            'nric_no' => 'required|string',
            'registration_no' => 'required|string',
            'role_id' => 'required|integer',
            'email' => 'required|string',
            'team_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'contact_no' => 'required|string',
            'designation_id' => 'required|integer',
            'is_incharge' => '',
            'designation_period_start_date' => 'required',
            'designation_period_end_date' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'account_status' => 'required',
            'document' => 'mimes:png,jpg,jpeg,pdf|max:10240'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->document == '') {

            $staffEmail = StaffManagement::where(
                ['id' => $request->id]
            )
                ->select('email')
                ->first();
            StaffManagement::where(
                ['id' => $request->id]
            )->update([
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'registration_no' =>  $request->registration_no,
                'role_id' =>  $request->role_id,
                'email' =>  $request->email,
                'team_id' =>  $request->team_id,
                'branch_id' =>  $request->branch_id,
                'contact_no' =>  $request->contact_no,
                'designation_id' =>  $request->designation_id,
                'is_incharge' =>  $request->is_incharge,
                'designation_period_start_date' =>  $request->designation_period_start_date,
                'designation_period_end_date' =>  $request->designation_period_end_date,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $request->document,
                'status' => $request->account_status
            ]);

            $updateDetails = [
                'email' => $request->email,
                'name' => $request->name,
            ];
            DB::table('users')
                ->where('email', $staffEmail->email)
                ->update($updateDetails);

            if ($staffEmail->email != $request->email) {
                $toEmail    =   $request->email;
                $data       =   ['name' => $request->name];
                try {
                    Mail::to($toEmail)->send(new UpdateEmail($data));
                } catch (Exception $e) {
                    return response()->json(["message" => $e->getMessage(), "code" => 500]);
                }
            }

            $userId = DB::table('users')
                ->select('id')
                ->where('email', $request->email)->first();

            ScreenAccessRoles::where('staff_id', $userId->id)->delete();

            $defaultAcc = DB::table('default_role_access')
                ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                ->where('default_role_access.role_id', $request->role_id)
                ->get();

            $hospital = HospitalBranchManagement::where('id', $request->branch_id)->first();

            if ($defaultAcc) {
                foreach ($defaultAcc as $key) {
                    $screen = [
                        'module_id' => $key->module_id,
                        'sub_module_id' => $key->sub_module_id,
                        'screen_id' => $key->screen_id,
                        'hospital_id' => $hospital->hospital_id,
                        'branch_id' => $request->branch_id,
                        'team_id' => $request->team_id,
                        'staff_id' => $userId->id,
                        'access_screen' => '1',
                        'read_writes' => '1',
                        'read_only' => '0',
                        'added_by' => $request->added_by,

                    ];
                    if (ScreenAccessRoles::where($screen)->count() == 0) {
                        ScreenAccessRoles::Create($screen);
                    }
                }
            }

            return response()->json(["message" => "Staff Management has updated successfully", "code" => 200]);
        } else {
            $files = $request->file('document');
            $isUploaded = upload_file($files, 'StaffManagement');

            $staffEmail = StaffManagement::where(
                ['id' => $request->id]
            )
                ->select('email')
                ->first();


            StaffManagement::where(
                ['id' => $request->id]
            )->update([
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'registration_no' =>  $request->registration_no,
                'role_id' =>  $request->role_id,
                'email' =>  $request->email,
                'team_id' =>  $request->team_id,
                'branch_id' =>  $request->branch_id,
                'contact_no' =>  $request->contact_no,
                'designation_id' =>  $request->designation_id,
                'is_incharge' =>  $request->is_incharge,
                'designation_period_start_date' =>  $request->designation_period_start_date,
                'designation_period_end_date' =>  $request->designation_period_end_date,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>   $isUploaded->getData()->path,
                'status' => $request->account_status
            ]);


            DB::table('users')
                ->where('email', $staffEmail->email)
                ->update(['email' => $request->email]);

            if ($staffEmail->email != $request->email) {
                $toEmail    =   $request->email;
                $data       =   ['name' => $request->name];
                try {
                    Mail::to($toEmail)->send(new UpdateEmail($data));
                } catch (Exception $e) {
                    return response()->json(["message" => $e->getMessage(), "code" => 500]);
                }
            }

            $userId = DB::table('users')
                ->select('id')
                ->where('email', $request->email)->first();

            ScreenAccessRoles::where('staff_id', $userId->id)->delete();

            $hospital = HospitalBranchManagement::where('id', $request->branch_id)->first();

            $defaultAcc = DB::table('default_role_access')
                ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                ->where('default_role_access.role_id', $request->role_id)
                ->get();

            if ($defaultAcc) {
                foreach ($defaultAcc as $key) {
                    $screen = [
                        'module_id' => $key->module_id,
                        'sub_module_id' => $key->sub_module_id,
                        'screen_id' => $key->screen_id,
                        'hospital_id' => $hospital->hospital_id,
                        'branch_id' => $request->branch_id,
                        'team_id' => $request->team_id,
                        'staff_id' => $userId->id,
                        'access_screen' => '1',
                        'read_writes' => '1',
                        'read_only' => '0',
                        'added_by' => $request->added_by,

                    ];
                    if (ScreenAccessRoles::where($screen)->count() == 0) {
                        ScreenAccessRoles::Create($screen);
                    }
                }
            }



            return response()->json(["message" => "Staff Management has updated successfully", "code" => 200]);
        }
    }

    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        StaffManagement::where(
            ['id' => $request->id]
        )->update([
            'status' => '0',
            'added_by' => $request->added_by
        ]);

        return response()->json(["message" => "Staff Removed From System!", "code" => 200]);
    }

    public function transferstaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'old_branch_id' => 'required|integer',
            'new_branch_id' => 'required|integer',
            'staff_id' => 'required|integer',
            'start_date' => 'required',
            'end_date' => '',
            'document' => ''

        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        if (($request->document == "null") || empty($request->document)) {
            $staffadd = [
                'added_by' =>  $request->added_by,
                'old_branch_id' =>  $request->old_branch_id,
                'new_branch_id' =>  $request->new_branch_id,
                'staff_id' =>  $request->staff_id,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $request->document,
                'status' => "1"
            ];
        } else {
            $files = $request->file('document');
            $isUploaded = upload_file($files, 'TransferStaff');
            $staffadd = [
                'added_by' =>  $request->added_by,
                'old_branch_id' =>  $request->old_branch_id,
                'new_branch_id' =>  $request->new_branch_id,
                'staff_id' =>  $request->staff_id,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $isUploaded->getData()->path,
                'status' => "1"
            ];
        }

        try {
            StaffManagement::where(
                ['id' => $request->staff_id]
            )->update([
                'added_by' =>  $request->added_by,
                'branch_id' =>  $request->new_branch_id
            ]);
            $HOD = Mentari_Staff_Transfer::firstOrCreate($staffadd);
        } catch (Exception $e) {
            return response()->json(["message" => $e->getMessage(), 'Staff' => $staffadd, "code" => 200]);
        }
        return response()->json(["message" => "Transfer Successfully!", "code" => 200]);
    }

    public function getAdminList()
    {
        $users = DB::table('staff_management')
            ->select(
                'roles.role_name',
                'staff_management.id',
                'staff_management.name',
                'general_setting.section_value as designation_name',
                'hospital_branch__details.hospital_branch_name',
                'users.id as staffId'
            )
            ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
            ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
            ->join('roles', 'staff_management.role_id', '=', 'roles.id')
            ->join('users', 'staff_management.email', '=', 'users.email')
            ->where('staff_management.status', '=', '1')
            ->where('roles.code', '!=', 'null')
            ->orderBy('staff_management.name', 'asc')
            ->get();
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function setSystemAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }



        $user = DB::table('staff_management')
            ->select('staff_management.email', 'staff_management.branch_id', 'staff_management.team_id', 'hospital_branch__details.hospital_id')
            ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
            ->where('staff_management.id', $request->id)->first();

        $userId = DB::table('users')
            ->select('id')
            ->where('email', $user->email)->first();

        $role = roles::where('code', 'superadmin')->first();
        StaffManagement::where('id', $request->id)->update(['role_id' => $role->id]);

        $defaultAcc = DB::table('default_role_access')
            ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
            ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
            ->where('default_role_access.role_id', $role->id)
            ->get();


        if ($defaultAcc) {
            foreach ($defaultAcc as $key) {
                $screen = [
                    'module_id' => $key->module_id,
                    'sub_module_id' => $key->sub_module_id,
                    'screen_id' => $key->screen_id,
                    'hospital_id' => $user->hospital_id,
                    'branch_id' => $user->branch_id,
                    'team_id' => $user->team_id,
                    'staff_id' => $userId->id,
                    'access_screen' => '1',
                    'read_writes' => '1',
                    'read_only' => '0',
                    'added_by' => $request->added_by,

                ];

                if (ScreenAccessRoles::where($screen)->count() == 0) {
                    ScreenAccessRoles::Create($screen);
                }
            }
            return response()->json(["message" => "Role Assigned Successfully", "code" => 200]);
        }
    }

    public function removeUserAccess(Request $request)
    {
        $user = DB::table('staff_management')
            ->select('email')
            ->where('id', $request->id)->first();

        $userId = DB::table('users')
            ->select('id')
            ->where('email', $user->email)->first();

        ScreenAccessRoles::where('staff_id', $userId->id)->delete();
        StaffManagement::where('id', $request->id)->update(['role_id' => '']);

        return response()->json(["message" => "User Access successfully removed", "code" => 200]);
    }

    public function getRoleCode(Request $request)
    {
        $users = DB::table('staff_management')
            ->select('roles.code')
            ->join('roles', 'staff_management.role_id', '=', 'roles.id')
            ->where('staff_management.email', '=', $request->email)
            ->first();

        return response()->json(["message" => "Staff Management Details", 'list' => $users, "code" => 200]);
    }
}
