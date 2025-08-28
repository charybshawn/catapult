<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use Livewire\Component;
use App\Models\TaskType;
use App\Models\TimeCard;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class LogoutTaskModal extends Component implements HasForms
{
    use InteractsWithForms;
    
    public $showModal = false;
    public $selectedTasks = [];
    public $commonTasks = [];
    public $timeCardId;
    public $data = [];
    
    protected $listeners = ['showLogoutModal' => 'openModal'];
    
    public function mount()
    {
        $this->loadCommonTasks();
        $this->form->fill();
    }
    
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TagsInput::make('selectedTasks')
                    ->label('What did you work on?')
                    ->placeholder('Select tasks or type custom tasks...')
                    ->suggestions($this->getTaskSuggestions())
                    ->required()
                    ->helperText('Select from common tasks or type your own.')
                    ->reorderable(),
            ])
            ->statePath('data');
    }
    
    public function loadCommonTasks()
    {
        $this->commonTasks = TaskType::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('category')
            ->map(function ($tasks) {
                return $tasks->pluck('name', 'name')->toArray();
            })
            ->toArray();
    }
    
    protected function getTaskSuggestions(): array
    {
        $suggestions = [];
        
        foreach ($this->commonTasks as $category => $tasks) {
            foreach ($tasks as $task) {
                $suggestions[] = $task;
            }
        }
        
        return $suggestions;
    }
    
    public function openModal($timeCardId)
    {
        $this->timeCardId = $timeCardId;
        $this->form->fill(['selectedTasks' => []]);
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->form->fill(['selectedTasks' => []]);
    }
    
    public function submit()
    {
        $data = $this->form->getState();
        
        $timeCard = TimeCard::find($this->timeCardId);
        
        if ($timeCard && !empty($data['selectedTasks'])) {
            // Add the selected tasks to the time card
            $timeCard->addTasks($data['selectedTasks']);
            
            // Clock out (but don't logout)
            $timeCard->clockOut();
            
            $this->closeModal();
            
            // Refresh all time clock widgets on the page
            $this->js('window.dispatchEvent(new CustomEvent("refresh-time-widgets"));');
        }
    }
    
    public function render()
    {
        return view('livewire.logout-task-modal');
    }
}
