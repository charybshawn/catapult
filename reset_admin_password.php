<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'charybshawn@gmail.com')->first();
if ($user) {
    $user->password = Hash::make('password123');
    $user->save();
    echo "Admin password reset successfully\n";
    echo "Email: charybshawn@gmail.com\n";
    echo "Password: password123\n";
} else {
    echo "Admin user not found\n";
}