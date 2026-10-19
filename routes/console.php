<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('shop:admin {email} {--name=Administrator}', function () {
    $email=$this->argument('email');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->error('Enter a valid email.'); return 1; }
    $password=$this->secret('Choose a password (at least 12 characters)');
    if (strlen($password ?? '')<12) { $this->error('Password must contain at least 12 characters.'); return 1; }
    $user=User::firstOrNew(['email'=>$email]);
    $user->name=$this->option('name'); $user->password=Hash::make($password); $user->is_admin=true; $user->save();
    $this->info('Administrator saved. Sign in at /admin/login.');
})->purpose('Create or update a shop administrator with a hidden password prompt');
