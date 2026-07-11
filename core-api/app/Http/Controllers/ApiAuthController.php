<?php
namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class ApiAuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email|unique:vendors,email',
            'password' => 'required|string',
            'type' => 'nullable|string',
        ];

        if ($request->type === 'vendor') {
            $rules['company_name'] = 'required|string|max:255';
            $rules['contact_person'] = 'required|string|max:255';
            $rules['phone'] = 'nullable|string|max:50';
            $rules['address'] = 'nullable|string';
            $rules['website'] = 'nullable|string|max:255';
            $rules['bank_name'] = 'nullable|string|max:255';
            $rules['account_holder_name'] = 'nullable|string|max:255';
            $rules['account_number'] = 'nullable|string|max:255';
            $rules['iban'] = 'nullable|string|max:255';
            $rules['swift_code'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $result = $this->authService->register($request->only([
            'name', 'email', 'password', 'type', 'company_name', 'contact_person',
            'phone', 'address', 'website', 'bank_name', 'account_holder_name',
            'account_number', 'iban', 'swift_code',
        ]));

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Registration successful',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($request->email, $request->password);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Login successful',
        ], 200);
    }
}
