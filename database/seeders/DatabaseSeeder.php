<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Builds the catalog, then imports the collection. Both steps print a
     * reconciliation report; nothing in the source data is silently resolved.
     */
    public function run(): void
    {
        $this->command->call('fiesta:import-catalog', ['--fresh' => true]);
        $this->command->call('fiesta:import-holdings');
    }
}
