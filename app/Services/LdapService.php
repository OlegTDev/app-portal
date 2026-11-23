<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\Attributes\AccountControl;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

/**
 * LDAP/Active Directory Service wrapper.
 *
 * Uses ldaprecord/ldaprecord-laravel for AD operations.
 * Configuration is loaded from config/ldap.php.
 */
class LdapService
{
    private Connection $connection;
    protected array $config;

    public function __construct(?string $connectionName = null)
    {
        $this->connection = $connectionName
            ? Container::getConnection($connectionName)
            : Container::getDefaultConnection();

        $defaultConnection = $connectionName ?? config('ldap.default', 'default');
        $connectionConfig = \is_string($defaultConnection)
            ? config("ldap.connections.{$defaultConnection}")
            : null;

        $this->config = \is_array($connectionConfig) ? $connectionConfig : [];
    }

    /**
     * Authenticate user with username and password via LDAP bind.
     *
     * Returns user data if auth succeeded, null otherwise.
     */
    public function authenticate(string $username, string $password): ?array
    {
        if (trim($password) === '') {
            return null;
        }

        try {
            $user = $this->resolveLdapUser($username);

            if (! $user instanceof LdapUser) {
                return null;
            }

            // Bind with user's actual DN
            if (! $this->connection->auth()->attempt($user->getDn(), $password)) {
                return null;
            }

            return $this->mapUserForSync($user);
        } catch (LdapRecordException $e) {
            Log::error('LDAP authenticate error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find user by sAMAccountName (no password check).
     */
    public function findUser(string $username): ?array
    {
        try {
            $user = $this->resolveLdapUser($username);

            if (! $user instanceof LdapUser) {
                return null;
            }

            $memberof = $user->getAttribute('memberof') ?? [];

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
            Log::error('LDAP findUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get full AD user data for sync (all attributes).
     */
    public function syncUser(string $username): ?array
    {
        try {
            $user = $this->resolveLdapUser($username);

            if (! $user instanceof LdapUser) {
                return null;
            }

            $memberof = $user->getAttribute('memberof') ?? [];

            return $this->mapUserForSync($user);
        } catch (LdapRecordException $e) {
            Log::error('LDAP syncUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Search users by query (for autocomplete).
     */
    public function searchUsers(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        try {
            $ldapQuery = LdapUser::query();

            /** @var \LdapRecord\Query\Collection<int, LdapUser>|array $users */
            $users = $this->newUserQuery()
                ->select([
                    'cn',
                    'displayName',
                    'sAMAccountName',
                    'mail',
                    'telephoneNumber',
                    'title',
                    'department',
                ])
                ->where(function ($q) use ($query): void {
                    $q->whereStartsWith('cn', $query)
                        ->orWhereStartsWith('displayName', $query)
                        ->orWhereStartsWith('sAMAccountName', $query)
                        ->orWhereStartsWith('mail', $query);
                })
                ->limit(20)
                ->get();

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
            Log::error('LDAP searchUsers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all active users for sync.
     */
    public function getAllActiveUsers(): array
    {
        try {
            $ldapQuery = $this->newUserQuery();

            /** @phpstan-ignore method.notFound */
            $ldapQuery->whereEnabled();

            if (! empty($this->config['users_dn'])) {
                $ldapQuery->in($this->config['users_dn']);
            }

            /** @var iterable<int, LdapUser> $users */
            $users = $ldapQuery
                ->select([
                    'cn',
                    'displayName',
                    'sAMAccountName',
                    'mail',
                    'telephoneNumber',
                    'title',
                    'department',
                    'physicalDeliveryOfficeName',
                ])
                ->paginate(500);

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
            Log::error('LDAP getAllActiveUsers error: ' . $e->getMessage());
            return [];
        }
    }


    protected function newUserQuery(): \LdapRecord\Query\Model\Builder
    {
        $query = LdapUser::query();

        if (! empty($this->config['users_dn']) && is_string($this->config['users_dn'])) {
            $query->in($this->config['users_dn']);
        }

        return $query;
    }

    protected function resolveLdapUser(string $login): ?LdapUser
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        $query = LdapUser::query();

        if (! empty($this->config['users_dn']) && is_string($this->config['users_dn'])) {
            $query->in($this->config['users_dn']);
        }

        // 1. Формат DOMAIN\login -> извлекаем login и ищем по sAMAccountName
        if (str_contains($login, '\\')) {
            $parts = explode('\\', $login, 2);
            $samAccountName = trim($parts[1] ?? '');

            if ($samAccountName === '') {
                return null;
            }

            /** @var LdapUser|null $user */
            $user = $this->newUserQuery()
                ->where('samaccountname', '=', $samAccountName)
                ->first();

            return $user;
        }

        // 2. Формат login@domain.com -> ищем по userPrincipalName (или sAMAccountName до @)
        if (str_contains($login, '@')) {
            /** @var LdapUser|null $user */
            $user = $query->where('userprincipalname', '=', $login)->first();

            if ($user instanceof LdapUser) {
                return $user;
            }

            // Запасной вариант: если UPN в домене отличается от почтового домена (например, mail = ivanov@company.ru, а UPN = ivanov@ad.local)
            $samAccountName = trim(explode('@', $login, 2)[0]);

            if ($samAccountName === '') {
                return null;
            }

            /** @var LdapUser|null $userBySam */
            $userBySam = $this->newUserQuery()
                ->where('samaccountname', '=', $samAccountName)
                ->first();

            return $userBySam;
        }

        // 3. Обычный формат login -> ищем по sAMAccountName
        /** @var LdapUser|null $user */
        $user = $this->newUserQuery()
            ->where('samaccountname', '=', $login)
            ->first();

        return $user;
    }

    protected function mapUserForSync(LdapUser $user): array
    {
        $memberof = $user->getAttribute('memberof') ?? [];
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
    }

    /**
     * Check if AD account is active.
     */
    protected function isAccountActive(LdapUser $user): bool
    {
        return ! $user->accountControl()->hasFlag(AccountControl::ACCOUNTDISABLE);
    }

    /**
     * Get account control flags.
     */
    protected function getAccountControl(LdapUser $user): array
    {
        $ac = $user->accountControl();

        return [
            'raw' => $ac->getValue(),
            'disabled' => $ac->hasFlag(AccountControl::ACCOUNTDISABLE),
            'password_expired' => $ac->hasFlag(AccountControl::PASSWORD_EXPIRED),
            'password_not_required' => $ac->hasFlag(AccountControl::PASSWD_NOTREQD),
            'needs_change' => $user->getFirstAttribute('pwdLastSet') === '0',
        ];
    }
}
