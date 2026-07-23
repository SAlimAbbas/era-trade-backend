<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Buyer;
use App\Models\AdminLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Log in a user (Supplier, Buyer, or Admin).
     */
    /**
     * Log in a user (Supplier, Buyer, or Admin).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        // 1. Search primary users table first
        $user = User::where('email', $email)->first();

        if ($user) {
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid email or password.'
                ], 401);
            }

            if (isset($user->status) && $user->status === 'inactive') {
                return response()->json([
                    'message' => 'Your account is currently inactive. Please contact support.'
                ], 403);
            }

            $membertype = strtolower($user->membertype ?? $user->types ?? 'supplier');
            $type = ($membertype === 'buyer') ? 'buyer' : 'supplier';

            $token = $user->createToken($type . '-token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name ?? 'User',
                    'email' => $user->email,
                    'type' => $type,
                    'membertype' => $membertype,
                ]
            ]);
        }

        // 2. Legacy fallback for old accounts in buyers table
        $buyer = Buyer::where('email', $email)->first();
        if ($buyer && Hash::check($password, $buyer->password)) {
            $token = $buyer->createToken('buyer-token')->plainTextToken;
            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => [
                    'id' => $buyer->id,
                    'name' => $buyer->name,
                    'email' => $buyer->email,
                    'type' => 'buyer',
                    'membertype' => 'buyer',
                ]
            ]);
        }

        // 3. Fallback for admin_login
        $admin = AdminLogin::where('email', $email)->first();
        if ($admin && Hash::check($password, $admin->password)) {
            $token = $admin->createToken('admin-token')->plainTextToken;
            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => [
                    'id' => $admin->id,
                    'name' => $admin->username ?? 'Admin',
                    'email' => $admin->email,
                    'type' => 'admin',
                    'membertype' => 'admin',
                ]
            ]);
        }

        return response()->json([
            'message' => 'Invalid email or password.'
        ], 401);
    }

    /**
     * Register a new User/Supplier.
     */
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email|max:50',
            'password' => 'required|string|min:6|max:200',
            'phone' => 'required|string|max:15',
            'country' => 'required|string|max:200',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'zipcode' => 'nullable|integer',
            'membertype' => 'nullable|string|in:supplier,buyer,user',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim($request->email));
        $membertype = strtolower($request->input('membertype', 'supplier'));

        $userData = [
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? '',
            'types' => $membertype,
            'country' => $request->country ?? '',
            'state' => $request->state ?? '',
            'city' => $request->city ?? '',
            'zipcode' => $request->zipcode ?? 0,
            'membertype' => $membertype,
            'verification_status' => 1,
            'verification_id' => bin2hex(random_bytes(16)),
            'memberid' => 'MBR-' . rand(1000, 9999),
            'plan' => 'Basic',
            'otp' => 0,
            'status' => 'active',
            'reset_token' => '',
            'token_expiry' => now()->addDays(30),
            'admin_id' => 0,
            'added_on' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $user = User::create($userData);

        // If company details provided during registration, store in supplier table
        if ($membertype === 'supplier' && $request->filled('company_name')) {
            \App\Models\Supplier::create([
                'SuplierID' => 'SUP-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'Name' => $request->name,
                'CompanyName' => $request->company_name,
                'Email' => $email,
                'Contact' => $request->phone,
                'Country' => $request->country,
                'City' => $request->city ?? 'Mumbai',
                'State' => $request->state ?? 'Maharashtra',
                'Zipcode' => (string)($request->zipcode ?? '400001'),
                'Address' => ($request->city ?? '') . ', ' . ($request->state ?? '') . ', ' . $request->country,
                'Description' => 'Exporter and supplier of ' . ($request->industry ?? 'General Goods'),
                'Businesscategories' => $request->industry ?? 'General Trade',
                'Primarybusiness' => 'Manufacturer & Exporter',
                'added_by' => $user->id,
                'status' => 0,
                'PaymentTerms' => 'LC / TT',
                'logo' => 'demo.png',
                'Banner' => '0',
                'Crn' => '',
                'Cey' => '',
                'importcode' => '0',
                'Whatsappno' => $request->phone,
                'Alternateno' => '0',
                'recsuppliy' => 0,
                'admin_id' => 0,
                'added_on' => now()->toDateString(),
                'created_at' => now()->toDateString(),
                'updated_at' => now(),
            ]);
        }

        $token = $user->createToken('supplier-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $membertype,
                'membertype' => $membertype
            ]
        ], 201);
    }

    /**
     * Register a new Buyer (stored in USERS table first).
     */
    public function registerBuyer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email|max:100',
            'password' => 'required|string|min:6|max:255',
            'phone' => 'required|string|max:20',
            'country' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim($request->email));

        // 1. Create primary account in USERS table FIRST
        $userData = [
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? '',
            'types' => 'buyer',
            'country' => $request->country ?? '',
            'state' => '',
            'city' => '',
            'zipcode' => 0,
            'membertype' => 'buyer',
            'verification_status' => 1,
            'verification_id' => bin2hex(random_bytes(16)),
            'memberid' => 'MBR-' . rand(1000, 9999),
            'plan' => 'Basic',
            'otp' => 0,
            'status' => 'active',
            'reset_token' => '',
            'token_expiry' => now()->addDays(30),
            'admin_id' => 0,
            'added_on' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $user = User::create($userData);

        // 2. Also create/sync record in BUYERS table
        Buyer::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'country' => $request->country,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('buyer-token')->plainTextToken;

        return response()->json([
            'message' => 'Buyer registration successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'buyer',
                'membertype' => 'buyer'
            ]
        ], 201);
    }

    /**
     * Log out the current authenticated session.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
