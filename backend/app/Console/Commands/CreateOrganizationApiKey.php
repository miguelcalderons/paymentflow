<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

class CreateOrganizationApiKey extends Command
{
    protected $signature = 'organization:create-api-key
                            {organization}
                            {--name=default}';

    protected $description = 'Create an API key for an organization';

    public function handle(): int
    {
        $organization = Organization::findOrFail(
            $this->argument('organization')
        );

        $plainTextKey = 'pf_' . bin2hex(random_bytes(32));

        $organization->apiKeys()->create([
            'name' => $this->option('name'),
            'key_hash' => hash('sha256', $plainTextKey),
        ]);

        $this->info('API key created.');
        $this->warn('Save this key now. It will not be shown again.');

        $this->line($plainTextKey);

        return self::SUCCESS;
    }
}
