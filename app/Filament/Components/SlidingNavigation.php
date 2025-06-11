<?php

namespace App\Filament\Components;

use Filament\View\Components\Component;

class SlidingNavigation extends Component
{
    protected string $view = 'filament.navigation.sliding-navigation';
    
    public function render()
    {
        return view($this->view);
    }
}