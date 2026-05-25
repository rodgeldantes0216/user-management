<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LargeUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the seeder to create a large number of users in chunks.
     */
    public function run(): void
    {
        $total = 12000;
        $chunk = 2000;
        $created = 0;

        while ($created < $total) {
            $count = min($chunk, $total - $created);
            User::factory()->count($count)->create();
            $created += $count;

            if ($this->command) {
                $this->command->info("Created {$created}/{$total} users...");
            }
        }
    }
}
