<?php

namespace App\DataForge\Task;

use App\Models\User;
use App\Models\ServiceInstall;
use Illuminate\Support\Str;
use DataForge\Task;

class Services extends Task
{
    public function guestInstall($params = [])
    {
        $ip = request()->ip();
        $ipKey = str_replace(['.', ':'], '-', $ip); // handle IPv4 and IPv6
        $email = "location-service-{$ipKey}@dataforge.local";
        // $email = 'location-service@dataforge.local';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Location API Client (' . $ip . ')',
                'password' => bcrypt(Str::random(32)),
            ]
        );

        // Revoke existing location tokens (optional)
        // $user->tokens()->where('name', 'location-api-client')->delete();

        $token = $user->createToken('location-api-client-' . $user->id, ['location:read'])->plainTextToken;

        // Track install
        ServiceInstall::create([
            'type' => 'location',
            'subtype' => $params['subtype'] ?? 'vue',
            'token_id' => $user->tokens->last()?->id,
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
            'meta' => [
                'source' => $params['source'] ?? 'setup-script',
                'version' => $params['version'] ?? null,
            ]
        ]);

        return [
            'token' => $token,
            'expires_in' => 31536000
        ];
    }
}
