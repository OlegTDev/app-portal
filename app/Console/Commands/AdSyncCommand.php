<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\LdapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ad:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync employees from Active Directory';

    protected LdapService $ldap;

    /**
     * Execute the console command.
     */
    public function handle(LdapService $ldap): int
    {
        $this->ldap = $ldap;

        if (!config('ldap.enabled', true)) {
            $this->warn('LDAP is disabled. Set LDAP_ENABLED=true in .env');
            return Command::FAILURE;
        }

        $this->info('Starting AD sync...');
        $start = microtime(true);

        try {
            // Get all active users from AD
            $adUsers = $this->ldap->getAllActiveUsers();

            if (empty($adUsers)) {
                $this->warn('No active users found in AD.');
                return Command::SUCCESS;
            }

            $this->info("Found " . count($adUsers) . " active users in AD");

            // Track usernames found in AD
            $foundUsernames = [];

            DB::beginTransaction();

            try {
                foreach ($adUsers as $adUser) {
                    $foundUsernames[] = $adUser['username'];

                    // Upsert employee
                    $employee = Employee::updateOrCreate(
                        ['username' => $adUser['username']],
                        [
                            'email' => $adUser['email'] ?? null,
                            'full_name' => $adUser['full_name'] ?? null,
                            'position' => $adUser['position'] ?? null,
                            'phone' => $adUser['phone'] ?? null,
                            'phone_additional' => $adUser['phone_additional'] ?? null,
                            'office' => $adUser['office'] ?? null,
                            'department_name' => $adUser['department_name'] ?? null,
                            'ad_data' => $adUser['ad_data'] ?? [],
                            'is_active' => $adUser['is_active'] ?? true,
                        ]
                    );

                    if ($employee->wasChanged()) {
                        $this->line("  ✓ Updated: {$adUser['full_name']} ({$adUser['username']})");
                    } else {
                        $this->line("  - Unchanged: {$adUser['full_name']} ({$adUser['username']})");
                    }
                }

                // Mark inactive users (not found in AD)
                $inactiveCount = Employee::whereNotIn('username', $foundUsernames)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                if ($inactiveCount > 0) {
                    $this->warn("  ⚠ Marked $inactiveCount employees as inactive (not found in AD)");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            $this->error('AD sync failed: ' . $e->getMessage());
            Log::error('AD sync error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $duration = round(microtime(true) - $start, 2);
        $this->info("AD sync completed in {$duration}s");

        return Command::SUCCESS;
    }
}
