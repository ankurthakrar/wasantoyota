<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\customclasses\Corefunctions;
use App\Models\User;
use App\Models\Auth;
use DB;
use Illuminate\Support\Facades\File;
use Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use App\customclasses\AdditionalFunctions;

class UserController extends Controller
{
    public function __construct()
    {
        $jsonData = file_get_contents('php://input');
        $input= json_decode($jsonData,true);
        $this->Corefunctions = new \App\customclasses\Corefunctions;
        $this->AdditionalFunctions = new \App\customclasses\AdditionalFunctions;
        $this->request = $this -> AdditionalFunctions->validateAPI( $input );
        //$userinfo = $this->AdditionalFunctions->validateAccessToken($input);
        //$userinfo = $this->Corefunctions->convertToArray($userinfo);
        
        if (!empty($userinfo)) {
            $this->userinfo = $userinfo;
        }
      
    }

    public function register()
    { 
        try {
            $inputParameters = $this->request['parameters'];
            $requiredFields = array('firstname', 'lastname', 'email', 'username','password');
            foreach ($requiredFields as $key => $value) {
                if (!isset($inputParameters[$value]) || $inputParameters[$value] == '') throw new Exception(config('constants.VALIDATIONS.REQUIRED_FIELD'),404);

            }
            $email = $inputParameters["email"];
            if(!filter_var($email, FILTER_VALIDATE_EMAIL))throw new Exception(config('constants.VALIDATIONS.INVALID_EMAIL'),405);
            
            $userData = User::checkUserByEmail(strtolower($email));
            if(!empty( $userData )) throw new Exception(config('constants.VALIDATIONS.EMAIL_EXIST'),406);

            $passwordHash = Hash::make($inputParameters["password"]);
            //$userkey = $this->Corefunctions->generateUniqueKey('8', 'users', 'user_uuid');
            $userId = Auth::addUserInfo($inputParameters, $passwordHash);
            $userDetails = User::userByID($userId);

            $userInfo['id'] = $userDetails['id'];
            $userInfo['firstname'] = $userDetails['first_name'];
            $userInfo['lastname'] = $userDetails['last_name'];
            $userInfo['email'] = $userDetails['email'];
            $response["data"]['userdetails'] = $userInfo;
            $response['code'] = config('constants.API_CODES.SUCCESS');
            $response['status'] = config('constants.API_CODES.SUCCESS_STATUS');
            $response['message'] = config('constants.VALIDATIONS.REGISTRATION_SUCCESS');
            return response()->json($response,200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage(),'code'=> $e->getCode(),'status'=>config('constants.API_CODES.ERROR_STATUS')], config('constants.API_CODES.ERROR'));
        
        }

    }




    public function login(Request $request)
    {
        try {
            $inputParameters = $this->request['parameters'];
           
            $requiredFields = array('username', 'password');
            foreach ($requiredFields as $key => $value) {
                if (!isset($inputParameters[$value]) || $inputParameters[$value] == '') {
                    if (!isset($inputParameters[$value]) || $inputParameters[$value] == '') throw new Exception(config('constants.VALIDATIONS.REQUIRED_FIELD'),411);
                }
            }
            //$email = $inputParameters["email"];
            //if(!filter_var($email, FILTER_VALIDATE_EMAIL))throw new Exception(config('constants.VALIDATIONS.INVALID_EMAIL'));
           // $userData = user::checkUserByEmail(strtolower($email));
             
            $userDetails = Auth::validateUserByUserName(strtolower($inputParameters['username']));

            if(empty($userDetails)) throw new Exception(config('constants.VALIDATIONS.USERNAME_NOT_EXIST'),412);
            
            elseif($userDetails['password'] != '' && !Hash::check($inputParameters['password'], $userDetails['password']))throw new Exception(config('constants.VALIDATIONS.WRONG_CREDENTIAL'),413);

            $userId = $userDetails['id'];
        
            $tokenGenerate = md5(TOKENSECRET . $userDetails['email'] . $userDetails['password'] . $userDetails['id'].rand() );
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
           
            $expiry_timestamp = time();
            $authtokenid = DB::table('auth_tokens')->insertGetId(array(
                'user_id' => $userId,
                'auth_token' => $tokenGenerate,
                'ip_address' => $ip,
                'expiry_on' => strtotime('+1 year', $expiry_timestamp),
                'created_at' => Carbon\Carbon::now()
            ));
           
            $userid = $userDetails['id'];
            $userInfo['email'] = $userDetails['email'];
            $userInfo['firstname'] = $userDetails['first_name'];
            $userInfo['lastname'] = $userDetails['last_name'];
            $userInfo['role'] = $userDetails['role'];
            $userInfo['team'] = $userDetails['team'];
            $userInfo['status'] = $userDetails['status'];
            $userInfo['userid'] = $userDetails['id'];
            $response["data"]['userdetails'] = $userInfo;
            $response["data"]['accesstoken'] = $tokenGenerate;
            $response['code'] = config('constants.API_CODES.SUCCESS');
            $response['status'] = config('constants.API_CODES.SUCCESS_STATUS');
            $response['message'] = config('constants.VALIDATIONS.LOGIN_SUCCESS');
            return response()->json($response,200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage(),'code'=> $e->getCode(),'status'=>config('constants.API_CODES.ERROR_STATUS')], 400);
        }
    }



    

    
  


}