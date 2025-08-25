<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Services\CaddyService;
use Illuminate\Console\Command;

class CaddyDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'caddy:domain 
                            {action : The action to perform (add, remove, list, status, sync)}
                            {domain? : The domain to add or remove}
                            {--all : Apply action to all institution domains}';

    /**
     * The console command description.
     */
    protected $description = 'Manage domains in Caddy configuration';

    /**
     * Execute the console command.
     */
    public function handle(CaddyService $caddyService): int
    {
        $action = $this->argument('action');
        $domain = $this->argument('domain');
        $all = $this->option('all');

        switch ($action) {
            case 'add':
                return $this->addDomain($caddyService, $domain, $all);
            case 'remove':
                return $this->removeDomain($caddyService, $domain, $all);
            case 'list':
                return $this->listDomains($caddyService);
            case 'status':
                return $this->checkStatus($caddyService);
            case 'sync':
                return $this->syncDomains($caddyService);
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: add, remove, list, status, sync');
                return Command::FAILURE;
        }
    }

    private function addDomain(CaddyService $caddyService, ?string $domain, bool $all): int
    {
        if (!$caddyService->isHealthy()) {
            $this->error('Caddy API is not available. Please check if Caddy is running.');
            return Command::FAILURE;
        }

        if ($all) {
            return $this->addAllDomains($caddyService);
        }

        if (!$domain) {
            $this->error('Domain is required when not using --all option.');
            return Command::FAILURE;
        }

        if (!$caddyService->isValidDomain($domain)) {
            $this->error("Invalid domain format: {$domain}");
            return Command::FAILURE;
        }

        $this->info("Adding domain {$domain} to Caddy...");
        
        if ($caddyService->addDomain($domain)) {
            $this->info("✅ Domain {$domain} added successfully.");
            return Command::SUCCESS;
        }

        $this->error("❌ Failed to add domain {$domain}.");
        return Command::FAILURE;
    }

    private function removeDomain(CaddyService $caddyService, ?string $domain, bool $all): int
    {
        if (!$caddyService->isHealthy()) {
            $this->error('Caddy API is not available. Please check if Caddy is running.');
            return Command::FAILURE;
        }

        if ($all) {
            return $this->removeAllDomains($caddyService);
        }

        if (!$domain) {
            $this->error('Domain is required when not using --all option.');
            return Command::FAILURE;
        }

        $this->info("Removing domain {$domain} from Caddy...");
        
        if ($caddyService->removeDomain($domain)) {
            $this->info("✅ Domain {$domain} removed successfully.");
            return Command::SUCCESS;
        }

        $this->error("❌ Failed to remove domain {$domain}.");
        return Command::FAILURE;
    }

    private function listDomains(CaddyService $caddyService): int
    {
        if (!$caddyService->isHealthy()) {
            $this->error('Caddy API is not available. Please check if Caddy is running.');
            return Command::FAILURE;
        }

        $domains = $caddyService->getConfiguredDomains();
        
        if (empty($domains)) {
            $this->info('No domains configured in Caddy.');
            return Command::SUCCESS;
        }

        $this->info('Configured domains in Caddy:');
        foreach ($domains as $domain) {
            $this->line("  • {$domain}");
        }

        return Command::SUCCESS;
    }

    private function checkStatus(CaddyService $caddyService): int
    {
        $this->info('Checking Caddy API status...');
        
        if ($caddyService->isHealthy()) {
            $domains = $caddyService->getConfiguredDomains();
            $domainCount = count($domains);
            
            $this->info("✅ Caddy API is healthy with {$domainCount} configured domains.");
            
            // Check institution domains
            $institutions = Institution::whereNotNull('domain')->get();
            $institutionDomains = $institutions->pluck('domain')->toArray();
            
            $this->info("\nInstitution domain status:");
            foreach ($institutions as $institution) {
                $status = in_array($institution->domain, $domains) ? '✅' : '❌';
                $this->line("  {$status} {$institution->name}: {$institution->domain}");
            }
            
            return Command::SUCCESS;
        }

        $this->error('❌ Caddy API is not available.');
        return Command::FAILURE;
    }

    private function syncDomains(CaddyService $caddyService): int
    {
        if (!$caddyService->isHealthy()) {
            $this->error('Caddy API is not available. Please check if Caddy is running.');
            return Command::FAILURE;
        }

        $this->info('Syncing institution domains with Caddy...');
        
        $institutions = Institution::whereNotNull('domain')->get();
        $configuredDomains = $caddyService->getConfiguredDomains();
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($institutions as $institution) {
            if (in_array($institution->domain, $configuredDomains)) {
                $this->line("  ✅ {$institution->domain} already configured");
                continue;
            }
            
            if (!$caddyService->isValidDomain($institution->domain)) {
                $this->line("  ❌ {$institution->domain} invalid format");
                $failureCount++;
                continue;
            }
            
            if ($caddyService->addDomain($institution->domain)) {
                $this->line("  ✅ {$institution->domain} added");
                $successCount++;
            } else {
                $this->line("  ❌ {$institution->domain} failed to add");
                $failureCount++;
            }
        }
        
        $this->info("\nSync completed: {$successCount} added, {$failureCount} failed.");
        
        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function addAllDomains(CaddyService $caddyService): int
    {
        $institutions = Institution::whereNotNull('domain')->get();
        
        if ($institutions->isEmpty()) {
            $this->info('No institution domains found.');
            return Command::SUCCESS;
        }
        
        $this->info("Adding {$institutions->count()} institution domains to Caddy...");
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($institutions as $institution) {
            if (!$caddyService->isValidDomain($institution->domain)) {
                $this->line("  ❌ {$institution->domain} invalid format");
                $failureCount++;
                continue;
            }
            
            if ($caddyService->addDomain($institution->domain)) {
                $this->line("  ✅ {$institution->domain} added");
                $successCount++;
            } else {
                $this->line("  ❌ {$institution->domain} failed");
                $failureCount++;
            }
        }
        
        $this->info("\nCompleted: {$successCount} added, {$failureCount} failed.");
        
        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function removeAllDomains(CaddyService $caddyService): int
    {
        $domains = $caddyService->getConfiguredDomains();
        
        if (empty($domains)) {
            $this->info('No domains configured in Caddy.');
            return Command::SUCCESS;
        }
        
        $this->info("Removing {count($domains)} domains from Caddy...");
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($domains as $domain) {
            if ($caddyService->removeDomain($domain)) {
                $this->line("  ✅ {$domain} removed");
                $successCount++;
            } else {
                $this->line("  ❌ {$domain} failed");
                $failureCount++;
            }
        }
        
        $this->info("\nCompleted: {$successCount} removed, {$failureCount} failed.");
        
        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}