<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class SetLoginFlag
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        // Set a flag in the session to indicate this is a fresh login
        session(['just_logged_in' => true]);
    }
}