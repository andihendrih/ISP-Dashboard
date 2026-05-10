<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Auto-create / sync portal user account for a CustomerProfile.
 *
 * Each registered customer gets a User row (role=customer) so they can
 * log in to the customer portal once it's built. Username is derived from
 * email when available, otherwise from customer_code.
 */
class CustomerAccountService
{
    /**
     * Ensure a portal user exists for $profile. Returns the User and the
     * plain password (only when newly generated, null otherwise).
     *
     * @return array{user: User, plain_password: ?string}
     */
    public function ensureForCustomer(CustomerProfile $profile): array
    {
        $existing = User::where('customer_profile_id', $profile->id)->first();
        if ($existing) {
            return ['user' => $existing, 'plain_password' => null];
        }

        $role = Role::where('name', Role::CUSTOMER)->first();
        $email = $this->resolveEmail($profile);
        $plain = $this->generatePassword();

        $user = User::create([
            'name'                => $profile->full_name ?: ($profile->customer_code ?? 'Pelanggan'),
            'email'               => $email,
            'password'            => Hash::make($plain),
            'role_id'             => $role?->id,
            'customer_profile_id' => $profile->id,
            'is_active'           => true,
        ]);

        return ['user' => $user, 'plain_password' => $plain];
    }

    protected function resolveEmail(CustomerProfile $profile): string
    {
        $candidate = $profile->email;
        if ($candidate && !User::where('email', $candidate)->exists()) {
            return $candidate;
        }
        // Fallback: customer_code@portal.local — guaranteed unique because
        // customer_code itself is unique. If still collides, append id.
        $base = strtolower($profile->customer_code ?: ('cust-'.$profile->id));
        $email = $base.'@portal.ahnet.local';
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base.'-'.$i.'@portal.ahnet.local';
            $i++;
        }
        return $email;
    }

    protected function generatePassword(int $length = 10): string
    {
        return Str::password($length, true, true, false, false);
    }
}
