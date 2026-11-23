<?php

namespace App\Services;

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\Attributes\AccountControl;

/**
 * LDAP/Active Directory Service wrapper.
 *
 * Uses ldaprecord/ldaprecord-laravel for AD operations.
 * Configuration is loaded from config/ldap.php.
 */
class LdapService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('ldap.default') ?? [];
    }

    /**
     * Authenticate user with username and password via LDAP bind.
     *
     * Returns user data if auth succeeded, null otherwise.
     */
    public function authenticate(string $username, string $password): ?array
    {
        try {
            $ldap = Container::make(
                new Connection($this->config)
            );

            // Bind with user credentials
            if (!$ldap->auth()->attempt($username, $password)) {
                return null;
            }

            // Auth succeeded — fetch full user data
            return $this->syncUser($username);
        } catch (LdapRecordException $e) {
            \Log::error('LDAP authenticate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find user by sAMAccountName (no password check).
     */
    public function findUser(string $username): ?array
    {
        try {
            $ldap = Container::make(
                new Connection($this->config)
            );

            $user = $ldap->search()
                ->in($this->config['users_dn'] ?? 'CN=Users,DC=domain,DC=local')
                ->where('sAMAccountName', $username)
                ->first();

            if (!$user) {
                return null;
            }

            $memberof = [];
            foreach ($user->getGroups() as $group) {
                $memberof[] = $group->getDn();
            }

            return [
                'username' => $user->getFirstAttribute('sAMAccountName'),
                'dn' => $user->getDn(),
                'email' => $user->getFirstAttribute('mail'),
                'full_name' => $user->getFirstAttribute('displayName')
                    ?? $user->getFirstAttribute('cn'),
                'position' => $user->getFirstAttribute('title'),
                'phone' => $user->getFirstAttribute('telephoneNumber'),
                'phone_additional' => $user->getFirstAttribute('otherTelephone'),
                'office' => $user->getFirstAttribute('physicalDeliveryOfficeName'),
                'department' => $user->getFirstAttribute('department'),
                'memberof' => $memberof,
                'ad_data' => [
                    'dn' => $user->getDn(),
                    'memberof' => $memberof,
                    'account_control' => $this->getAccountControl($user),
                ],
            ];
        } catch (LdapRecordException $e) {
            \Log::error('LDAP findUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get full AD user data for sync (all attributes).
     */
    public function syncUser(string $username): ?array
    {
        try {
            $ldap = Container::make(
                new Connection($this->config)
            );

            $user = $ldap->search()
                ->in($this->config['users_dn'] ?? 'CN=Users,DC=domain,DC=local')
                ->where('sAMAccountName', $username)
                ->first();

            if (!$user) {
                return null;
            }

            $memberof = [];
            foreach ($user->getGroups() as $group) {
                $memberof[] = $group->getDn();
            }

            return [
                'username' => $user->getFirstAttribute('sAMAccountName'),
                'email' => $user->getFirstAttribute('mail'),
                'full_name' => $user->getFirstAttribute('displayName')
                    ?? $user->getFirstAttribute('cn'),
                'position' => $user->getFirstAttribute('title'),
                'rank' => $user->getFirstAttribute('description'),
                'phone' => $user->getFirstAttribute('telephoneNumber'),
                'phone_additional' => $user->getFirstAttribute('otherTelephone'),
                'office' => $user->getFirstAttribute('physicalDeliveryOfficeName'),
                'department_name' => $user->getFirstAttribute('department'),
                'ad_data' => [
                    'dn' => $user->getDn(),
                    'memberof' => $memberof,
                    'account_control' => $this->getAccountControl($user),
                    'when_changed' => $user->getFirstAttribute('whenChanged'),
                    'user_principal_name' => $user->getFirstAttribute('userPrincipalName'),
                ],
                'is_active' => $this->isAccountActive($user),
            ];
        } catch (LdapRecordException $e) {
            \Log::error('LDAP syncUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Search users by query (for autocomplete).
     */
    public function searchUsers(string $query): array
    {
        try {
            $ldap = Container::make(
                new Connection($this->config)
            );

            $users = $ldap->search()
                ->in($this->config['users_dn'] ?? 'CN=Users,DC=domain,DC=local')
                ->whereAny([
                    'cn',
                    'displayName',
                    'sAMAccountName',
                    'mail',
                ], 'like', "{$query}*")
                ->limit(20)
                ->get(['cn', 'displayName', 'sAMAccountName', 'mail', 'telephoneNumber', 'title', 'department']);

            $results = [];
            foreach ($users as $user) {
                $results[] = [
                    'username' => $user->getFirstAttribute('sAMAccountName'),
                    'full_name' => $user->getFirstAttribute('displayName')
                        ?? $user->getFirstAttribute('cn'),
                    'email' => $user->getFirstAttribute('mail'),
                    'phone' => $user->getFirstAttribute('telephoneNumber'),
                    'position' => $user->getFirstAttribute('title'),
                    'department' => $user->getFirstAttribute('department'),
                ];
            }

            return $results;
        } catch (LdapRecordException $e) {
            \Log::error('LDAP searchUsers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all active users for sync.
     */
    public function getAllActiveUsers(): array
    {
        try {
            $ldap = Container::make(
                new Connection($this->config)
            );

            $users = $ldap->search()
                ->in($this->config['users_dn'] ?? 'CN=Users,DC=domain,DC=local')
                ->where('userAccountControl', AccountControl::NORMAL_ACCOUNT)
                ->whereNot('userAccountControl', AccountControl::ACCOUNTDISABLE)
                ->get(['cn', 'displayName', 'sAMAccountName', 'mail', 'telephoneNumber',
                       'title', 'department', 'physicalDeliveryOfficeName']);

            $results = [];
            foreach ($users as $user) {
                $results[] = [
                    'username' => $user->getFirstAttribute('sAMAccountName'),
                    'full_name' => $user->getFirstAttribute('displayName')
                        ?? $user->getFirstAttribute('cn'),
                    'email' => $user->getFirstAttribute('mail'),
                    'phone' => $user->getFirstAttribute('telephoneNumber'),
                    'position' => $user->getFirstAttribute('title'),
                    'department_name' => $user->getFirstAttribute('department'),
                    'office' => $user->getFirstAttribute('physicalDeliveryOfficeName'),
                ];
            }

            return $results;
        } catch (LdapRecordException $e) {
            \Log::error('LDAP getAllActiveUsers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if AD account is active.
     */
    protected function isAccountActive(\LdapRecord\Models\Model $user): bool
    {
        try {
            $ac = $user->getAccountControl();
            if (!$ac) {
                return true;
            }
            return !$ac->isAccountDisabled();
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get account control flags.
     */
    protected function getAccountControl(\LdapRecord\Models\Model $user): array
    {
        try {
            $ac = $user->getAccountControl();
            if (!$ac) {
                return [];
            }

            return [
                'raw' => $ac->getValue(),
                'disabled' => $ac->isAccountDisabled(),
                'password_expired' => $ac->isPasswordExpired(),
                'needs_change' => $ac->isPasswordNotRequired(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
