<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AuthLineController extends Controller
{
    public function redirect(Request $request)
    {
        $query = http_build_query([
            'client_id'             => config('services.line.client_id'),
            'redirect_uri'          => config('services.line.redirect'),
            'response_type'         => 'code',
            'scope'                 => 'profile openid email',
            'state'                 => $request->root(), // 空白會導致錯誤, 防呆要加上
            'bot_prompt'            => config('services.line.bot_prompt'),
            'disable_auto_login'    => true, // 停用自動登入
        ]);

        return redirect('https://access.line.me/oauth2/v2.1/authorize?' . $query);
    }

    public function callback(Request $request)
    {
        $attributes = $request->validate([
            'code'              => 'nullable',
            'state'             => 'nullable',
            'error'             => 'nullable',
            'error_description' => 'nullable',
        ]);

        try {
            // 如果有錯誤, 直接丟出
            if (isset($attributes['error'])) throw new \Exception($attributes['error_description']);

            // 請求 access token
            $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type'    => 'authorization_code',
                'code'          => $attributes['code'],
                'redirect_uri'  => config('services.line.redirect'),
                'client_id'     => config('services.line.client_id'),
                'client_secret' => config('services.line.client_secret'),
            ]);

            if (!$response->ok()) throw new \Exception($response->json('error_description'));

            $idToken = str_replace(['-', '_'], ['+', '/'], $response->json('id_token'));

            if (count($parts = explode('.', $idToken)) !== 3) throw new \Exception('無效的 id_token');

            list($base64header, $base64payload, $signature) = $parts;

            // Base64 解码
            $payload = json_decode(base64_decode($base64payload), true);

            if (!isset($payload['email'])) throw new \Exception('尚未授權 email');

            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) throw new \Exception('無效的信箱格式');

            if (!$user = User::where('email', $payload['email'])->first()) throw new \Exception('尚未開通帳號, 請洽系統管理員');

            if (!$user->hasVerifiedEmail()) $user->markEmailAsVerified();

            auth()->login($user);

            return Inertia::location(redirect()->getTargetUrl() ?? route('dashboard', absolute: false));
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }
    }
}
