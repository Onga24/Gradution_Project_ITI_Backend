<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Otp;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;




class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password'
        ], [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'confirm_password.required' => 'Confirm password is required',
            'confirm_password.same' => 'Confirm password must match password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 400,
                "message" => "Validation errors",
                "errors" => $validator->errors()
            ], 400);
        }



    $user = User::where('email', $request->email)->first();

    if ($user) {
        if (is_null($user->email_verified_at)) {
            // user exists but not verified -> resend OTP flow
            return response()->json([
                'status' => 'pending_verification',
                'message' => 'User already registered but not verified. Please verify OTP.',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]
            ], 200);
        }

        // user exists and verified
        return response()->json([
            'status' => 'error',
            'message' => 'This email is already registered'
        ], 400);
    }




        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "email_verified_at" => null,

        ]);

    // create OTP and send email
    $throttleCheck = Otp::where('user_id', $user->id)
                        ->where('type', 'register')
                        ->where('created_at', '>', now()->subMinute())
                        ->exists();

    if ($throttleCheck) {
        // If somehow an OTP already created in the last minute (rare right after create),
        // we avoid spamming and return a clear message.
        return response()->json([
            'status' => 429,
            'message' => 'Please wait before requesting another OTP (1 minute limit).'
        ], 429);
    }

    $plainOtp = rand(100000, 999999);          // 6 digits
    $hashedOtp = Hash::make((string)$plainOtp); // hash when saving

    $otp = Otp::create([
        'user_id' => $user->id,
        'otp_code' => $hashedOtp,
        'type' => 'register',
        'expires_at' => now()->addMinutes(5),
    ]);

    // send OTP via email (or SMS). For email:
    Mail::to($user->email)->send(new SendOtpMail($plainOtp, 'register'));

    return response()->json([
        'status' => 201,
        'message' => 'User registered. An OTP has been sent to your email. Please verify within 5 minutes.',
        'data' => [
            'user_id' => $user->id,
            'email' => $user->email
        ]
    ], 201);
}



public function resendOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
    ]);

    if ($validator->fails()) {
        return response()->json(["status" => 400, 'errors' => $validator->errors()], 400);
    }

    $user = User::where('email', $request->email)->first();

    // throttle: cannot request more than one OTP in the same minute
    $recent = Otp::where('user_id', $user->id)
                 ->where('type', 'register')
                 ->where('created_at', '>', now()->subMinute())
                 ->first();

    if ($recent) {
        return response()->json([
            'status' => 429,
            'message' => 'You requested an OTP recently. Please wait 1 minute before requesting again.'
        ], 429);
    }

    // create and send new OTP
    $plainOtp = rand(100000, 999999);
    $hashedOtp = Hash::make((string)$plainOtp);

    Otp::create([
        'user_id' => $user->id,
        'otp_code' => $hashedOtp,
        'type' => 'register',
        'expires_at' => now()->addMinutes(5),
    ]);

    Mail::to($user->email)->send(new SendOtpMail($plainOtp, 'register'));

    return response()->json([
        'status' => 200,
        'message' => 'A new OTP has been sent to your email.'
    ]);
}


public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'otp_code' => 'required|digits:6'
    ]);

    if ($validator->fails()) {
        return response()->json(["status" => 400, "errors" => $validator->errors()], 400);
    }

    $user = User::where('email', $request->email)->first();

    // find the latest unused OTP for that user & type
    $otp = Otp::where('user_id', $user->id)
              ->where('type', 'register')
              ->where('is_used', false)
              ->latest()
              ->first();

    if (!$otp) {
        return response()->json(['status' => 400, 'message' => 'OTP not found or already used.'], 400);
    }

    // expired?
    if (now()->greaterThan($otp->expires_at)) {
        // delete expired OTP (you wanted to delete after expiry)
        $otp->delete();
        return response()->json(['status' => 400, 'message' => 'OTP has expired. Please request a new one.'], 400);
    }

    // check hashed otp
    if (!Hash::check((string)$request->otp_code, $otp->otp_code)) {
        return response()->json(['status' => 400, 'message' => 'Invalid OTP code.'], 400);
    }

    // success: mark user verified + remove OTP
$user->email_verified_at = now();
$user->save();

    // either delete or mark used
    $otp->delete(); // delete as you requested

    return response()->json(['status' => 200, 'message' => 'OTP verified successfully.']);
}



public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required',
    ], [
        'email.required' => 'Email is required',
        'email.email' => 'Email must be a valid email address',
        'password.required' => 'Password is required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            "status" => 400,
            "message" => "Validation errors",
            "errors" => $validator->errors()
        ], 400);
    }

    $validated = $validator->validated();

    $user = User::where('email', $validated['email'])->first();

    if ($user && Hash::check($validated['password'], $user->password)) {
        if (is_null($user->email_verified_at)) {
    return response()->json([
        'status' => 403,
        'message' => 'Please verify your email before logging in.'
    ], 403);
}

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'role' => $user->role, 
            'data' => $user,
            'token' => $token
        ], 200);
    }

    return response()->json([
        'status' => 401,
        'message' => 'Wrong email or password',
    ], 401);
}

    public function logout(Request $request)
    {
     $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logout successful',
        ]);
    }




public function forgotPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
    ]);

    if ($validator->fails()) {
        return response()->json(["status" => 400, "errors" => $validator->errors()], 400);
    }

    $user = User::where('email', $request->email)->first();

    $recent = Otp::where('user_id', $user->id)
                 ->where('type', 'forgot_password')
                 ->where('created_at', '>', now()->subMinute())
                 ->first();

    if ($recent) {
        return response()->json([
            'status' => 429,
            'message' => 'You requested an OTP recently. Please wait 1 minute before requesting again.'
        ], 429);
    }

    $plainOtp = rand(100000, 999999);
    $hashedOtp = Hash::make((string)$plainOtp);

    Otp::create([
        'user_id' => $user->id,
        'otp_code' => $hashedOtp,
        'type' => 'forgot_password',
        'expires_at' => now()->addMinutes(5),
    ]);

    Mail::to($user->email)->send(new SendOtpMail($plainOtp, 'forgot_password'));

    return response()->json([
        'status' => 200,
        'message' => 'OTP sent to your email for password reset.'
    ]);
}


public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'otp_code' => 'required|digits:6',
        'password' => 'required|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json(["status" => 400, "errors" => $validator->errors()], 400);
    }

    $user = User::where('email', $request->email)->first();

    $otp = Otp::where('user_id', $user->id)
              ->where('type', 'forgot_password')
              ->where('is_used', false)
              ->latest()
              ->first();

    if (!$otp || now()->greaterThan($otp->expires_at)) {
        return response()->json(['status' => 400, 'message' => 'OTP invalid or expired.'], 400);
    }

    if (!Hash::check((string)$request->otp_code, $otp->otp_code)) {
        return response()->json(['status' => 400, 'message' => 'Invalid OTP.'], 400);
    }

    $user->password = Hash::make($request->password);
    $user->save();

    $otp->is_used = true;
    $otp->save();

    return response()->json([
        'status' => 200,
        'message' => 'Password reset successfully. You can now login with your new password.'
    ]);
}





public function updateProfile(Request $request)
{
    $user = auth()->user(); 

    $validator = Validator::make($request->all(), [
        'name' => 'nullable|string|min:3|max:50',
        'password' => 'nullable|string|min:8|confirmed',
        'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json(["status" => 400, "errors" => $validator->errors()], 400);
    }

    $updated = false;

    if ($request->filled('name')) {
        $user->name = $request->name;
        $updated = true;
    }

    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
        $updated = true;
    }

    if ($request->hasFile('profile_picture')) {
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->profile_picture = $path; 
        $updated = true;
    }

    if ($updated) {
        $user->save();

        return response()->json([
            "status" => 200,
            "message" => "Profile updated successfully.",
            "user" => [
                "name" => $user->name,
                'role' => $user->role, 
                "profile_picture" => $user->profile_picture 
                    ? url('storage/' . $user->profile_picture) 
                    : null,
            ]
        ]);
    }

    return response()->json([
        "status" => 400,
        "message" => "No data provided to update."
    ], 400);
}





public function getMyProfile(Request $request)
{
    $user = auth()->user();

    return response()->json([
        "status" => 200,
        "user" => [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            'role' => $user->role, 
            "profile_picture" => $user->profile_picture 
                ? url('storage/' . $user->profile_picture) 
                : null,
            "created_at" => $user->created_at,
        ]
    ]);
}



// public function getMyProfile(Request $request)
// {
//     $user = auth()->user();

//     return response()->json([
//         "status" => 200,
//         "user" => $user
//     ]);
// }




    }

