<?php

use App\Models\Company;
use App\Models\CompanyMessage;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('company.{userId}', function ($user, $userId) {
    // Allow if the user is the company representative (has a company linked)
    $company = Company::where('user_id', $user->id)->first();
    return (int) $user->id === (int) $userId && $company !== null;
}, ['guards' => ['sanctum']]);

Broadcast::channel('tutor.{userId}', function ($user, $userId) {
    // Allow if the user is the tutor (has no company linked) and matches
    $company = Company::where('user_id', $user->id)->first();
    return (int) $user->id === (int) $userId && $company === null;
}, ['guards' => ['sanctum']]);
