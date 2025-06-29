<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'test@example.com')->first();
if (!$user) {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123')
    ]);
    echo "Test user created\n";
} else {
    $user->password = Hash::make('password123');
    $user->save();
    echo "Test user password updated\n";
}
echo "Email: test@example.com\n";
echo "Password: password123\n";