<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateCustomerLoginAction
{
    /**
     * Create a user account for a customer and link them
     */
    public function execute(Customer $customer, array $data): User
    {
        return DB::transaction(function () use ($customer, $data) {
            // Create user account for customer
            $user = User::create([
                'name' => $customer->contact_name,
                'email' => $customer->email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);
            
            // Assign customer role
            $user->assignRole('customer');
            
            // Link to customer
            $customer->update(['user_id' => $user->id]);
            
            // Send email if requested
            if ($data['send_credentials']) {
                $this->sendCredentialsEmail($customer, $user, $data['password']);
            }

            return $user;
        });
    }

    /**
     * Send credentials email to customer (placeholder for future implementation)
     */
    protected function sendCredentialsEmail(Customer $customer, User $user, string $password): void
    {
        // TODO: Implement email sending
        // This would typically use Laravel's mail system to send login credentials
        // For now, this is a placeholder method
    }
}