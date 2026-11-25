<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Fake AD service for local development.
 * Emulates LDAP with hardcoded test users.
 * Enable via LDAP_ENABLED=false in .env.
 */
class FakeLdapService
{
    /**
     * @var array<string, array> Test users
     */
    protected array $users = [
        'admin' => [
            'username' => 'admin',
            'email' => 'admin@domain.local',
            'full_name' => 'Администратор Домена',
            'position' => 'System Administrator',
            'phone' => '+7-000-000-0000',
            'office' => 'Офис 101',
            'department_name' => 'IT',
            'is_active' => true,
            'ad_data' => [
                'dn' => 'CN=admin,OU=Users,DC=domain,DC=local',
                'memberof' => ['CN=Domain Admins,CN=Users,DC=domain,DC=local'],
                'account_control' => [
                    'raw' => 512,
                    'disabled' => false,
                    'password_expired' => false,
                    'password_not_required' => false,
                    'needs_change' => false,
                ],
                'user_principal_name' => 'Administrator@domain.local',
            ],
        ],
        'svc-portal' => [
            'username' => 'svc-portal',
            'email' => 'svc-portal@domain.local',
            'full_name' => 'Сервис Портала',
            'position' => 'Service Account',
            'phone' => null,
            'office' => null,
            'department_name' => 'IT',
            'is_active' => true,
            'ad_data' => [
                'dn' => 'CN=svc-portal,OU=Users,DC=domain,DC=local',
                'memberof' => [],
                'account_control' => [
                    'raw' => 512,
                    'disabled' => false,
                    'password_expired' => false,
                    'password_not_required' => false,
                    'needs_change' => false,
                ],
                'user_principal_name' => 'svc-portal@domain.local',
            ],
        ],
        'ivanov.ivan' => [
            'username' => 'ivanov.ivan',
            'email' => 'ivanov.ivan@domain.local',
            'full_name' => 'Иванов Иван',
            'position' => 'Senior Developer',
            'phone' => '+7-999-111-1111',
            'office' => 'Офис 205',
            'department_name' => 'Разработка',
            'is_active' => true,
            'ad_data' => [
                'dn' => 'CN=Ivanov Ivan,OU=Employees,DC=domain,DC=local',
                'memberof' => ['CN=Developers,CN=Groups,DC=domain,DC=local'],
                'account_control' => [
                    'raw' => 512,
                    'disabled' => false,
                    'password_expired' => false,
                    'password_not_required' => false,
                    'needs_change' => false,
                ],
                'user_principal_name' => 'ivanov.ivan@domain.local',
            ],
        ],
        'petrov.petr' => [
            'username' => 'petrov.petr',
            'email' => 'petrov.petr@domain.local',
            'full_name' => 'Петров Петр',
            'position' => 'HR Manager',
            'phone' => '+7-999-222-2222',
            'office' => 'Офис 302',
            'department_name' => 'HR',
            'is_active' => true,
            'ad_data' => [
                'dn' => 'CN=Petrov Petr,OU=Employees,DC=domain,DC=local',
                'memberof' => ['CN=HR,CN=Groups,DC=domain,DC=local'],
                'account_control' => [
                    'raw' => 512,
                    'disabled' => false,
                    'password_expired' => false,
                    'password_not_required' => false,
                    'needs_change' => false,
                ],
                'user_principal_name' => 'petrov.petr@domain.local',
            ],
        ],
        'sidorova.maria' => [
            'username' => 'sidorova.maria',
            'email' => 'sidorova.maria@domain.local',
            'full_name' => 'Сидорова Мария',
            'position' => 'Accountant',
            'phone' => '+7-999-333-3333',
            'office' => 'Офис 401',
            'department_name' => 'Финансы',
            'is_active' => true,
            'ad_data' => [
                'dn' => 'CN=Sidorova Maria,OU=Employees,DC=domain,DC=local',
                'memberof' => ['CN=Finance,CN=Groups,DC=domain,DC=local'],
                'account_control' => [
                    'raw' => 512,
                    'disabled' => false,
                    'password_expired' => false,
                    'password_not_required' => false,
                    'needs_change' => false,
                ],
                'user_principal_name' => 'sidorova.maria@domain.local',
            ],
        ],
    ];

    /**
     * Authenticate user with username and password.
     * Any password works in fake mode.
     */
    public function authenticate(string $username, string $password): ?array
    {
        if (trim($password) === '') {
            return null;
        }

        // Normalize username: DOMAIN\login -> login
        if (str_contains($username, '\\')) {
            $parts = explode('\\', $username, 2);
            $username = trim($parts[1] ?? '');
        }

        // Normalize username: login@domain -> login
        if (str_contains($username, '@')) {
            $username = trim(explode('@', $username, 2)[0]);
        }

        // Find user by username or sAMAccountName
        $user = null;
        foreach ($this->users as $u) {
            if ($u['username'] === $username) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            Log::info('FakeAD: user not found: ' . $username);

            return null;
        }

        if (!$user['is_active']) {
            Log::info('FakeAD: user is disabled: ' . $username);

            return null;
        }

        Log::info('FakeAD: user logged in: ' . $username);

        return $user;
    }

    /**
     * Find user by username (no password check).
     */
    public function findUser(string $username): ?array
    {
        $username = trim($username);

        if (str_contains($username, '\\')) {
            $parts = explode('\\', $username, 2);
            $username = trim($parts[1] ?? '');
        }

        if (str_contains($username, '@')) {
            $username = trim(explode('@', $username, 2)[0]);
        }

        foreach ($this->users as $u) {
            if ($u['username'] === $username) {
                return $u;
            }
        }

        return null;
    }

    /**
     * Get all active users for sync.
     */
    public function getAllActiveUsers(): array
    {
        $results = [];

        foreach ($this->users as $user) {
            if ($user['is_active']) {
                $results[] = [
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'position' => $user['position'],
                    'department_name' => $user['department_name'],
                    'office' => $user['office'],
                ];
            }
        }

        return $results;
    }

    /**
     * Search users by query (for autocomplete).
     */
    public function searchUsers(string $query): array
    {
        $query = strtolower(trim($query));

        if ($query === '') {
            return [];
        }

        $results = [];

        foreach ($this->users as $user) {
            $searchable = strtolower(
                $user['username'] . ' ' .
                $user['full_name'] . ' ' .
                $user['email'] . ' ' .
                ($user['position'] ?? '')
            );

            if (str_contains($searchable, $query)) {
                $results[] = [
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'position' => $user['position'],
                    'department' => $user['department_name'],
                ];
            }
        }

        return $results;
    }

    /**
     * Get full AD user data for sync.
     */
    public function syncUser(string $username): ?array
    {
        return $this->findUser($username);
    }
}
