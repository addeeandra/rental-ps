<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SetupAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Admin User Setup');
        $this->info('----------------');
        $this->newLine();

        $name = $this->ask('Admin Name');
        $email = $this->ask('Admin Email');

        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email address.');
            return self::FAILURE;
        }

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->warn("⚠ User with email '{$email}' already exists.");
            
            if (!$this->confirm('Do you want to update this user to admin role?', true)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            $user->name = $name;
            $user->role = 'admin';
            $user->save();

            $this->newLine();
            $this->info('✓ User updated to admin role successfully!');
        } else {
            $password = $this->secret('Admin Password');
            $passwordConfirmation = $this->secret('Confirm Password');

            if ($password !== $passwordConfirmation) {
                $this->error('Passwords do not match.');
                return self::FAILURE;
            }

            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters.');
                return self::FAILURE;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
            ]);

            $this->newLine();
            $this->info('✓ Admin user created successfully!');
        }

        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
            ]
        );

        return self::SUCCESS;
    }
}
