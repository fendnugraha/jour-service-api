<?php

namespace Database\Seeders;

use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Contact;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Database\Seeders\AccountSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\ChartOfAccountSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'fend nugraha',
            'email' => 'fend@jour.com',
            'email_verified_at' => now(),
            'password' => 'user123',
        ]);

        Warehouse::create([
            'code' => 'HQT',
            'name' => 'HEADQUARTER',
            'address' => 'Bandung, Jawa Barat, ID, 40375',
            'chart_of_account_id' => 1
        ]);

        Role::create([
            'user_id' => 1,
            'warehouse_id' => 1,
            'role' => 'Administrator'
        ]);

        Role::create([
            'user_id' => 2,
            'warehouse_id' => 1,
            'role' => 'Administrator'
        ]);

        Contact::create([
            'name' => 'General',
            'type' => 'Customer',
            'Description' => 'General Customer',
        ]);

        ProductCategory::create([
            'name' => 'General',
            'prefix' => 'GNR',
        ]);

        $this->call([
            AccountSeeder::class,
            ChartOfAccountSeeder::class,
            // ProductSeeder::class
        ]);
    }
}
