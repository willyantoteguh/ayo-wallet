<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_users')->insert([
            'name' => 'Sha Admin',
            'email' => 'admin@ayo.com',
            'password' => bcrypt('bapakkaurahasia123')
        ]);
    }
}
