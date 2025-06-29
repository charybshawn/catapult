<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Create admin role if it doesn't exist
$adminRole = Role::firstOrCreate(['name' => 'admin']);
echo "Admin role ready\n";

// Assign admin role to test user
$testUser = User::where('email', 'test@example.com')->first();
if ($testUser) {
    $testUser->assignRole('admin');
    echo "Admin role assigned to test@example.com\n";
}

// Make sure admin user has admin role
$adminUser = User::where('email', 'charybshawn@gmail.com')->first();
if ($adminUser && !$adminUser->hasRole('admin')) {
    $adminUser->assignRole('admin');
    echo "Admin role confirmed for charybshawn@gmail.com\n";
}

echo "\nUsers with admin access:\n";
$admins = User::role('admin')->get(['email', 'name']);
foreach ($admins as $admin) {
    echo "- {$admin->email} ({$admin->name})\n";
}