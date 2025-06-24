<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TimeCard;
use Illuminate\Support\Facades\Auth;

class TimeClockWidget extends Component
{
    public $activeTimeCard;
    public $elapsedTime = '00:00:00';
    public $isActive = false;
    public $isFlagged = false;

    public function mount()
    {
        $this->loadActiveTimeCard();
    }

    public function loadActiveTimeCard()
    {
        if (Auth::check()) {
            $this->activeTimeCard = TimeCard::getActiveForUser(Auth::id());
            $this->isActive = $this->activeTimeCard !== null;
            $this->isFlagged = $this->activeTimeCard ? $this->activeTimeCard->requires_review : false;
            
            if ($this->activeTimeCard) {
                $this->updateElapsedTime();
            }
        }
    }

    public function updateElapsedTime()
    {
        if ($this->activeTimeCard) {
            $this->activeTimeCard->refresh(); // Refresh to get latest data
            $this->elapsedTime = $this->activeTimeCard->elapsed_time;
            $this->isFlagged = $this->activeTimeCard->requires_review;
            
            // Check if we need to flag this time card
            $this->activeTimeCard->checkAndFlagIfNeeded();
        }
    }

    public function clockOut()
    {
        if ($this->activeTimeCard) {
            $this->activeTimeCard->clockOut();
            
            // Optionally log out the user
            Auth::logout();
            
            return redirect()->route('filament.admin.auth.login');
        }
    }

    public function render()
    {
        return view('livewire.time-clock-widget');
    }
}
