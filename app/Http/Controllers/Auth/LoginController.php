<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PortalUser;
use App\Services\LdapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Authenticate user via LDAP and create/login portal session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $ldap = app(LdapService::class);

        // Authenticate via LDAP
        $userData = $ldap->authenticate($validated['username'], $validated['password']);

        if (!$userData) {
            return back()->withErrors([
                'username' => 'Неверный логин или пароль',
            ])->onlyInput('username');
        }

        // Find or create employee
        $employee = Employee::updateOrCreate(
            ['username' => $userData['username']],
            [
                'email' => $userData['email'] ?? null,
                'full_name' => $userData['full_name'] ?? null,
                'position' => $userData['position'] ?? null,
                'phone' => $userData['phone'] ?? null,
                'phone_additional' => $userData['phone_additional'] ?? null,
                'office' => $userData['office'] ?? null,
                'department_name' => $userData['department_name'] ?? null,
                'ad_data' => $userData['ad_data'] ?? [],
                'is_active' => $userData['is_active'] ?? true,
            ]
        );

        // Find or create portal user
        $portalUser = PortalUser::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'default_organization_code' => $employee->organization_code,
                'current_organization_code' => $employee->organization_code,
            ]
        );

        // Check if blocked
        if ($portalUser->blocked) {
            Log::warning('Blocked user attempted login: ' . $validated['username']);

            return back()->withErrors([
                'username' => 'Ваш аккаунт заблокирован. Обратитесь к администратору.',
            ])->onlyInput('username');
        }

        // Update last login
        $portalUser->update([
            'last_login_at' => now(),
            'last_action_url' => request()->fullUrl(),
        ]);

        // Log in via Sanctum session
        Auth::login($portalUser, true);

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /**
     * Logout the current user.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
