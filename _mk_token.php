<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
$u = User::where('email', 'meng.heang@tutor.com')->first();
$t = $u->createToken('diag')->plainTextToken;
file_put_contents(storage_path('app/diag_token.txt'), $t);
echo "token written, len=" . strlen($t) . "\n";
