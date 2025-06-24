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

    public function clockIn()
    {
        if (!$this->isActive) {
            $timeCard = TimeCard::create([
                'user_id' => Auth::id(),
                'clock_in' => now(),
                'work_date' => today(),
                'status' => 'active',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            $this->loadActiveTimeCard();
        }
    }

    protected $listeners = ['timeCardUpdated' => 'refreshWidget'];

    public function refreshWidget()
    {
        $this->loadActiveTimeCard();
    }

    public function clockOut()
    {
        if ($this->activeTimeCard) {
            // Emit event to show the logout task modal
            $this->dispatch('showLogoutModal', $this->activeTimeCard->id);
        }
    }

    public function render()
    {
        return view('livewire.time-clock-widget');
    }
}
