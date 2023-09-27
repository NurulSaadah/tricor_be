<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\ScreenModuleController;
use App\Http\Controllers\StaffManagementController;
use App\Http\Controllers\ForgetpasswordController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\DefaultRoleAccessController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SalesTransactionController;
use App\Models\SalesTransaction;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});






Route::group(['prefix' => 'pass'], function () {
    Route::post('/forgetpass', [ForgetpasswordController::class, 'forgetpass']);
    Route::post('/validatePasswordRule', [PasswordController::class, 'passwordRule']);
});
Route::group(['middleware' => ['jwt.verify']], function () {
    Route::get('/users/{from}/{to}', [UsersController::class, 'user_list']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/allowed-modules', [UsersController::class, 'get_user_role']);
});
Route::group(['prefix' => 'roles'], function () {
    Route::get('/list', [RolesController::class, 'index']);
    Route::get('/screenaccessroleslist', [RolesController::class, 'screenaccessroleslist']);
    Route::get('/branch-viewlist', [RolesController::class, 'branch_view_list']);
    Route::get('/system-admin-role', [RolesController::class, 'system_admin_role']);
    Route::post('/add', [RolesController::class, 'store']);
    Route::post('/update', [RolesController::class, 'update']);
    Route::post('/remove', [RolesController::class, 'delete']);
    Route::post('/assign', [RolesController::class, 'set_role']);
    Route::post('/role-byId', [RolesController::class, 'role_byId']);
});
Route::group(['prefix' => 'modules'], function () {
    Route::get('/list', [ModulesController::class, 'index']);
    Route::post('/add', [ModulesController::class, 'store']);
    Route::post('/update', [ModulesController::class, 'update']);
    Route::post('/remove', [ModulesController::class, 'delete']);
    Route::get('/get-child/{type}', [ModulesController::class, 'get_child_from_type']);
}); 

Route::group(['prefix' => 'reset'], function () {
    Route::post('/password', [PasswordController::class, 'resetPassword']);
    Route::post('/verifyAccount', [PasswordController::class, 'verifyAccount']);
    Route::post('/changePassword', [PasswordController::class, 'changePassword']);
});

Route::group(['prefix' => 'default-role-access'], function () {
    Route::post('/add', [DefaultRoleAccessController::class, 'store']);
    Route::post('/listbyId', [DefaultRoleAccessController::class, 'listbyId']);
    Route::post('/{id}/delete', [DefaultRoleAccessController::class, 'delete']);
});

Route::group(['prefix' => 'staff-record'], function () {
    Route::get('/getStaffList', [StaffManagementController::class, 'getStaffList']);
    Route::get('/getStaffList/{code}', [StaffManagementController::class, 'getStaffListbyCode']);
    Route::post('/getStaffListbyId', [StaffManagementController::class, 'getStaffListbyId']);
    Route::post('/createNewStaff', [StaffManagementController::class, 'createNewStaff']);
    Route::post('/isExistNric', [StaffManagementController::class, 'isExistNric']);
});

Route::group(['prefix' => 'role'], function () {
    Route::get('/getRoleList', [RolesController::class, 'getRoleList']);
});

Route::group(['prefix' => 'access'], function () {
    Route::post('/sidebar', [ScreenModuleController::class, 'getAccessScreenByUserId']);
});

Route::group(['prefix' => 'screen-module'], function () {
    Route::post('/add', [ScreenModuleController::class, 'storeModule']);
    Route::post('/add-sub-module', [ScreenModuleController::class, 'storeSubModule']);
    Route::post('/add-screen-page', [ScreenModuleController::class, 'storeScreen']);
    Route::get('/list', [ScreenModuleController::class, 'getModuleList']);
    Route::get('/sub-module-list', [ScreenModuleController::class, 'getSubModuleList']);
    Route::post('/sub-module-list-by-module-id', [ScreenModuleController::class, 'getSubModuleListByModuleId']);
    Route::post('/sub-module-list-by-sub-module-id', [ScreenModuleController::class, 'getSubModuleListBySubModuleId']);
    Route::post('/get-screen', [ScreenModuleController::class, 'getScreenByModuleAndSubModule']);
    Route::post('/assign-screen', [ScreenModuleController::class, 'addScreenRoles']);
    Route::post('/module-list-by-module-id', [ScreenModuleController::class, 'getModuleListByModuleId']);
    Route::post('/updateModule', [ScreenModuleController::class, 'updateModule']);
    Route::post('/removeModule', [ScreenModuleController::class, 'removeModule']);
    Route::post('/updateSubModule', [ScreenModuleController::class, 'updateSubModule']);
    Route::post('/removeSubModule', [ScreenModuleController::class, 'removeSubModule']);
    Route::get('/getScreenPageList', [ScreenModuleController::class, 'getScreenPageList']);
    Route::get('/getScreenModuleListById', [ScreenModuleController::class, 'getScreenModuleListById']);
    Route::post('/updateScreenModule', [ScreenModuleController::class, 'updateScreenModule']);
    Route::post('/removeScreenModule', [ScreenModuleController::class, 'removeScreenModule']);
    Route::post('/getScreenPageListByModuleIdAndSubModuleId', [ScreenModuleController::class, 'getScreenPageListByModuleIdAndSubModuleId']);
    Route::get('/getUserMatrixList', [ScreenModuleController::class, 'getUserMatrixList']);
    Route::post('/getUserMatrixListById', [ScreenModuleController::class, 'getUserMatrixListById']);//used
    Route::post('/updatescreenRole', [ScreenModuleController::class, 'UpdateScreenRole']);
    Route::post('/getScreenByModuleId', [ScreenModuleController::class, 'getScreenByModuleId']);
    Route::post('/assign-screen-byRoleId', [ScreenModuleController::class, 'addScreenByRolesId']);
});

Route::group(['prefix' => 'system-settings'], function () {
    Route::post('/insertOrupdate', [SystemSettingController::class, 'store']);
    Route::get('/get-setting/{section}', [SystemSettingController::class, 'get_setting']);
});

Route::group(['prefix' => 'general-setting'], function () {
    Route::post('/add', [GeneralSettingController::class, 'add']);
    Route::get('/lists', [GeneralSettingController::class, 'getListSetting']);
    //Route::post('/fetch', [GeneralSettingController::class, 'getSettingById']);
    Route::post('/update', [GeneralSettingController::class, 'update']);
    Route::post('/remove', [GeneralSettingController::class, 'remove']);
});

Route::group(['prefix' => 'inventory'], function () {
    Route::get('/getItemList', [InventoryController::class, 'getItemList']);
    Route::post('/getItemListbyCategory', [InventoryController::class, 'getItemListbyCategory']);
    Route::post('/getItembyId', [InventoryController::class, 'getItembyId']);
    Route::post('/createNewItem', [InventoryController::class, 'createNewItem']);
});

Route::group(['prefix' => 'sales'], function () {
    Route::post('/createNewSales', [SalesTransactionController::class, 'createNewSales']);
    Route::post('/updateSales', [SalesTransactionController::class, 'updateSales']);
    Route::post('/getSalesListbyStaffId', [SalesTransactionController::class, 'getSalesListbyStaffId']);
    Route::post('/getMonthlySales', [SalesTransactionController::class, 'getMonthlySales']);
    Route::post('/getMonthlySalesManager', [SalesTransactionController::class, 'getMonthlySalesManager']);
    Route::post('/getYearlySales', [SalesTransactionController::class, 'getYearlySales']);
    Route::post('/getSalesbyId', [SalesTransactionController::class, 'getSalesbyId']);
});