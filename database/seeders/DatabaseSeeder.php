<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * `php artisan db:seed` : données de démonstration, jamais en production.
     * Les vrais maquis sont créés par le Super Admin.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
