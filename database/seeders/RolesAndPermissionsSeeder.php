<?php

namespace Database\Seeders;

use App\Auth\RoleSync;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        RoleSync::run();
    }
}
