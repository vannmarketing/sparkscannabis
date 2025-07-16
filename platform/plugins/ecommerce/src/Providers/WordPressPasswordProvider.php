<?php

namespace Botble\Ecommerce\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class WordPressPasswordProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Extend the Auth facade with a custom password checker
        Auth::provider('eloquent', function ($app, array $config) {
            // Create a new instance of the Eloquent user provider directly
            $hasher = $app['hash'];
            $model = $config['model'] ?? '\App\Models\User';
            
            // Create the Eloquent user provider
            $provider = new \Illuminate\Auth\EloquentUserProvider($hasher, $model);

            // Return a decorator that adds WordPress password support
            return new class($provider) implements UserProvider {
                protected $provider;

                public function __construct(UserProvider $provider)
                {
                    $this->provider = $provider;
                }

                public function retrieveById($identifier)
                {
                    return $this->provider->retrieveById($identifier);
                }

                public function retrieveByToken($identifier, $token)
                {
                    return $this->provider->retrieveByToken($identifier, $token);
                }

                public function updateRememberToken(Authenticatable $user, $token)
                {
                    $this->provider->updateRememberToken($user, $token);
                }

                public function retrieveByCredentials(array $credentials)
                {
                    return $this->provider->retrieveByCredentials($credentials);
                }

                public function validateCredentials(Authenticatable $user, array $credentials)
                {
                    // If using standard Laravel authentication, delegate to the original provider
                    if (!isset($credentials['password'])) {
                        return $this->provider->validateCredentials($user, $credentials);
                    }

                    $plain = $credentials['password'];
                    $hashedPassword = $user->getAuthPassword();

                    // Debug information
                    \Log::debug('Validating credentials for user', [
                        'email' => $user->email,
                        'hash_starts_with' => substr($hashedPassword, 0, 6)
                    ]);

                    // Check if this is a WordPress password (prefixed with 'wp:')
                    if (Str::startsWith($hashedPassword, 'wp:')) {
                        $wpHash = substr($hashedPassword, 3); // Remove the 'wp:' prefix
                        
                        // Debug information
                        \Log::debug('Checking WordPress password', [
                            'email' => $user->email,
                            'hash_type' => Str::startsWith($wpHash, ['$P$', '$H$']) ? 'PHPass' : 
                                          (Str::startsWith($wpHash, '$2y$') ? 'Bcrypt' : 'MD5/Other'),
                            'hash_prefix' => substr($wpHash, 0, 4)
                        ]);
                        
                        // Directly check WordPress password without using Laravel's password_verify
                        $result = $this->checkWordPressPassword($plain, $wpHash, $user);
                        
                        // If successful, update the user's password to Laravel format
                        if ($result) {
                            $this->migrateToLaravelHash($user, $plain);
                        }
                        
                        return $result;
                    }

                    // Otherwise, use standard Laravel password checking
                    try {
                        return $this->provider->validateCredentials($user, $credentials);
                    } catch (\Exception $e) {
                        \Log::debug('Laravel validation failed, trying WordPress fallback', [
                            'error' => $e->getMessage()
                        ]);
                        // If Laravel validation fails, try our custom WordPress check as a fallback
                        return $this->checkWordPressPassword($plain, $hashedPassword, $user);
                    }
                }

                /**
                 * Check a WordPress password hash against a plain password
                 */
                protected function checkWordPressPassword(string $password, string $hash, Authenticatable $user): bool
                {
                    // Fix for double prefixed passwords (wp:$2y$)
                    if (Str::startsWith($hash, '$wp$2y$')) {
                        $wpHash = substr($hash, 3); // Remove the '$wp' prefix but keep the $
                        \Log::debug('Found double-prefixed WordPress password', [
                            'email' => $user->email,
                            'fixed_hash' => substr($wpHash, 0, 6) . '...'
                        ]);
                    } else {
                        // Remove the 'wp:' prefix we added
                        $wpHash = Str::startsWith($hash, 'wp:') ? substr($hash, 3) : $hash;
                    }
                    
                    // Debug information
                    \Log::debug('WordPress password check details', [
                        'email' => $user->email,
                        'hash_length' => strlen($wpHash),
                        'hash_start' => substr($wpHash, 0, 4)
                    ]);
                    
                    // WordPress uses PHPass library for password hashing
                    // This is a simplified implementation for different WordPress hash formats
                    
                    $result = false;
                    
                    // For PHPass hashes (start with $P$ or $H$)
                    if (Str::startsWith($wpHash, ['$P$', '$H$'])) {
                        // Use our custom PHPass implementation
                        $result = $this->checkWordPressPhpassHash($password, $wpHash);
                        \Log::debug('PHPass check result: ' . ($result ? 'success' : 'failed'));
                    }
                    // For bcrypt hashes (starts with $2y$)
                    else if (Str::startsWith($wpHash, '$2y$')) {
                        try {
                            // Use PHP's built-in password_verify for bcrypt
                            $result = password_verify($password, $wpHash);
                            \Log::debug('Bcrypt check result: ' . ($result ? 'success' : 'failed'));
                        } catch (\Exception $e) {
                            \Log::debug('Bcrypt verification error: ' . $e->getMessage());
                            // Fallback to direct comparison for problematic hashes
                            $result = false;
                        }
                    }
                    // For older MD5 hashes
                    else {
                        // Simple MD5 comparison for older WordPress versions
                        $result = ($wpHash === md5($password));
                        \Log::debug('MD5 check result: ' . ($result ? 'success' : 'failed'));
                    }
                    
                    return $result;
                }

                /**
                 * Check a WordPress PHPass hash
                 * This is a simplified implementation of the WordPress password checker
                 */
                protected function checkWordPressPhpassHash(string $password, string $hash): bool
                {
                    // WordPress uses PasswordHash class from PHPass
                    // This is a simplified implementation of the check_password method
                    
                    // The hash should start with $P$ or $H$
                    if (!Str::startsWith($hash, ['$P$', '$H$'])) {
                        return false;
                    }
                    
                    // Extract the salt from the hash
                    // WordPress uses the 4th character through 12th for the salt
                    $salt = substr($hash, 4, 8);
                    
                    // WordPress uses MD5 with specific iterations
                    // The 3rd character of the hash is the iteration count encoded as a base-64 character
                    $count = strpos('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $hash[3]);
                    if ($count < 7) {
                        $count = 7;
                    }
                    $count = pow(2, $count);
                    
                    // Generate the hash using the same method as WordPress
                    $hash_check = md5($salt . $password);
                    for ($i = 0; $i < $count; $i++) {
                        $hash_check = md5($hash_check . $password);
                    }
                    
                    // WordPress stores the hash with the settings as prefix
                    $stored_hash = substr($hash, 12);
                    
                    // Compare the generated hash with the stored hash
                    return $stored_hash === $hash_check;
                }

                /**
                 * Migrate a user from WordPress hash to Laravel hash
                 */
                protected function migrateToLaravelHash(Authenticatable $user, string $password): void
                {
                    // Update the user's password to use Laravel's hashing
                    $user->password = bcrypt($password);
                    $user->save();
                }
                
                public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
                {
                    // Check if this is a WordPress password that needs migration
                    $hashedPassword = $user->getAuthPassword();
                    
                    if (Str::startsWith($hashedPassword, 'wp:') && isset($credentials['password'])) {
                        $this->migrateToLaravelHash($user, $credentials['password']);
                    } else {
                        // Delegate to original provider if possible
                        if (method_exists($this->provider, 'rehashPasswordIfRequired')) {
                            $this->provider->rehashPasswordIfRequired($user, $credentials, $force);
                        }
                    }
                }
            };
        });
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        //
    }
}
