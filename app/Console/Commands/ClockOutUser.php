<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeCard;
use App\Models\User;

class ClockOutUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timecard:clock-out 
                            {user? : The ID or email of the user to clock out}
                            {--all : Clock out all active users}
                            {--force : Force clock out without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clock out users and end their active time cards';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            return $this->clockOutAllUsers();
        }

        $userIdentifier = $this->argument('user');
        
        if (!$userIdentifier) {
            $userIdentifier = $this->ask('Enter user ID or email');
        }

        $user = $this->findUser($userIdentifier);
        
        if (!$user) {
            $this->error('User not found!');
            return 1;
        }

        return $this->clockOutUser($user);
    }

    private function findUser($identifier): ?User
    {
        // Try to find by ID first
        if (is_numeric($identifier)) {
            $user = User::find($identifier);
            if ($user) return $user;
        }

        // Try to find by email
        return User::where('email', $identifier)->first();
    }

    private function clockOutUser(User $user): int
    {
        $activeTimeCard = TimeCard::getActiveForUser($user->id);
        
        if (!$activeTimeCard) {
            $this->info("User {$user->name} ({$user->email}) is not currently clocked in.");
            return 0;
        }

        if (!$this->option('force')) {
            $duration = $activeTimeCard->elapsed_time;
            $confirm = $this->confirm(
                "Clock out {$user->name} ({$user->email})? Current session: {$duration}"
            );
            
            if (!$confirm) {
                $this->info('Clock out cancelled.');
                return 0;
            }
        }

        $activeTimeCard->clockOut();
        
        $this->info("Successfully clocked out {$user->name} ({$user->email})");
        $this->line("Session duration: {$activeTimeCard->duration_formatted}");
        
        return 0;
    }

    private function clockOutAllUsers(): int
    {
        $activeTimeCards = TimeCard::where('status', 'active')->with('user')->get();
        
        if ($activeTimeCards->isEmpty()) {
            $this->info('No users are currently clocked in.');
            return 0;
        }

        $this->table(
            ['User', 'Email', 'Clocked In', 'Duration'],
            $activeTimeCards->map(fn($card) => [
                $card->user->name,
                $card->user->email,
                $card->clock_in->format('Y-m-d H:i:s'),
                $card->elapsed_time
            ])
        );

        if (!$this->option('force')) {
            $confirm = $this->confirm(
                'Clock out all ' . $activeTimeCards->count() . ' active users?'
            );
            
            if (!$confirm) {
                $this->info('Clock out cancelled.');
                return 0;
            }
        }

        $count = 0;
        foreach ($activeTimeCards as $timeCard) {
            $timeCard->clockOut();
            $count++;
        }

        $this->info("Successfully clocked out {$count} users.");
        return 0;
    }
}
