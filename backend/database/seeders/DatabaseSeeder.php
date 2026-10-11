<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AccessControlSeeder::class);

        // Demo/initial content is seeded only into an empty site, so re-running never overwrites edits.
        if (Page::count() === 0) {
            $this->call(ContentSeeder::class);
        } else {
            $this->command?->info('Pages already exist: content seeding skipped.');
        }

        $this->call(AdminUserSeeder::class);
    }
}
