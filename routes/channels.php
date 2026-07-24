<?php

use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Tutor;
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
    // Allow if the user is a supervisor (has a company via supervisor profile)
    $supervisor = CompanySupervisor::where('user_id', $user->id)->first();
    return (int) $user->id === (int) $userId && $supervisor !== null;
}, ['guards' => ['sanctum']]);

Broadcast::channel('tutor.{userId}', function ($user, $userId) {
    // Allow if the user is a tutor (not a supervisor) and matches
    $supervisor = CompanySupervisor::where('user_id', $user->id)->first();
    $tutor = Tutor::where('user_id', $user->id)->first();
    return (int) $user->id === (int) $userId && $supervisor === null && $tutor !== null;
}, ['guards' => ['sanctum']]);

Broadcast::channel('student.{userId}', function ($user, $userId) {
    // Allow if the user is a student and matches
    $student = \App\Models\Student::where('user_id', $user->id)->first();
    return (int) $user->id === (int) $userId && $student !== null;
}, ['guards' => ['sanctum']]);
