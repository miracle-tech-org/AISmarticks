<?php

use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 3)->create();

        User::create([
            'first_name' => 'AI',
            'last_name' => 'Agent',
            'email' => 'aiagent@smarticks.com',
            'password' => bcrypt('123$%^qweRTY'),
            'role' => '2',
            'timezone' => 'UTC',
            'type' => '1',
            'status' => '1',
            'invite_state' => '1',
            'invite_hash' => '',
            'time_format' => '2',
            'enable_kb_shortcuts' => '1',
            'locked' => '0',
            'open_on_this_page' => '0',
            'available' => '1',
        ]);
    }
}
