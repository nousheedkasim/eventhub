<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => $data['type'] ?? 'attendee',
            ]);

            if ($user->type === 'vendor') {
                Vendor::create([
                    'company_name' => $data['company_name'],
                    'contact_person' => $data['contact_person'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'website' => $data['website'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'account_holder_name' => $data['account_holder_name'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'iban' => $data['iban'] ?? null,
                    'swift_code' => $data['swift_code'] ?? null,
                    'kyc_status' => 'pending',
                    'is_active' => true,
                ]);
            }

            return $user;
        });

        if ($user->type === 'vendor') {
            $user->load('vendor');
        }

        $token = $user->createToken('mytoken')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new HttpResponseException(
                response()->json(['success' => false, 'data' => null, 'message' => 'Wrong email or password'], 401)
            );
        }

        $token = $user->createToken('mytoken')->plainTextToken;

        if ($user->type === 'vendor') {
            $user->load('vendor');
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
