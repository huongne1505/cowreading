<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if user already exists
        $existingUser = User::where('email', 'nghight281003@gmail.com')->first();
        
        if ($existingUser) {
            $this->info('User already exists with ID: ' . $existingUser->id);
            $this->info('Email: ' . $existingUser->email);
            $this->info('Name: ' . $existingUser->name);
            return;
        }

        // Create new user
        $user = User::create([
            'name' => 'Nghia Test User',
            'email' => 'nghight281003@gmail.com',
            'password' => Hash::make('Nghiai123'),
            'email_verified_at' => now(),
        ]);

        $this->info('Test user created successfully!');
        $this->info('ID: ' . $user->id);
        $this->info('Email: ' . $user->email);
        $this->info('Password: Nghiai123');
        
        // Create a token for this user
        $token = $user->createToken('auth-token')->plainTextToken;
        $this->info('Auth Token: ' . $token);
    }
}
