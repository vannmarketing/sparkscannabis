<?php

namespace Botble\Ecommerce\Commands;

use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Address;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportWordPressCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:import-wp-customers {file : The CSV file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import customers from WordPress CSV export with password preservation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting WordPress customer import...');

        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        // Open the CSV file
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$file}");
            return 1;
        }

        // Read the header row
        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("Empty or invalid CSV file");
            fclose($handle);
            return 1;
        }

        // Map column indexes
        $columns = $this->mapColumns($header);
        
        if (!isset($columns['email']) || !isset($columns['password'])) {
            $this->error("CSV file must contain 'email' and 'password' columns");
            fclose($handle);
            return 1;
        }

        $this->info('Processing customers...');
        $bar = $this->output->createProgressBar(count(file($file)) - 1);
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        
        try {
            // Process each row
            while (($row = fgetcsv($handle)) !== false) {
                $customerData = [];
                
                foreach ($columns as $field => $index) {
                    if ($index !== null && isset($row[$index])) {
                        $customerData[$field] = $row[$index];
                    }
                }
                
                // Validate required fields
                if (empty($customerData['email'])) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Check if customer already exists
                $existingCustomer = Customer::where('email', $customerData['email'])->first();
                
                if ($existingCustomer) {
                    // Update existing customer if needed
                    $this->info("Updating existing customer: {$customerData['email']}");
                    $result = $this->updateExistingCustomer($existingCustomer, $customerData);
                    if ($result) {
                        $imported++;
                        $this->info("Successfully updated customer: {$customerData['email']}");
                    } else {
                        $skipped++;
                        $this->info("Skipped updating customer: {$customerData['email']}");
                    }
                } else {
                    // Create new customer
                    $this->info("Creating new customer: {$customerData['email']}");
                    $result = $this->createNewCustomer($customerData);
                    if ($result) {
                        $imported++;
                        $this->info("Successfully created customer: {$customerData['email']}");
                    } else {
                        $skipped++;
                        $errors[] = "Failed to import customer: {$customerData['email']}";
                        $this->info("Failed to create customer: {$customerData['email']}");
                    }
                }
                
                $bar->advance();
            }
            
            DB::commit();
            
            $bar->finish();
            $this->newLine(2);
            
            $this->info("Import completed: {$imported} imported, {$skipped} skipped.");
            
            if (count($errors) > 0) {
                $this->warn("Errors encountered:");
                foreach ($errors as $error) {
                    $this->line(" - {$error}");
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during import: " . $e->getMessage());
            return 1;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Map CSV columns to database fields
     */
    protected function mapColumns(array $header): array
    {
        $columnMap = [
            'email' => null,
            'password' => null,
            'name' => null,
            'first_name' => null,
            'last_name' => null,
            'display_name' => null,
            'user_login' => null,
            'nickname' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'zip' => null,
        ];
        
        // Map column indexes based on header
        foreach ($header as $index => $columnName) {
            $columnName = strtolower(trim($columnName));
            
            // Map common column names
            switch ($columnName) {
                case 'email':
                case 'user_email':
                case 'email_address':
                    $columnMap['email'] = $index;
                    break;
                    
                case 'password':
                case 'user_pass':
                case 'pass':
                    $columnMap['password'] = $index;
                    break;
                    
                case 'name':
                    $columnMap['name'] = $index;
                    break;
                    
                case 'display_name':
                    $columnMap['display_name'] = $index;
                    break;
                    
                case 'user_login':
                case 'username':
                    $columnMap['user_login'] = $index;
                    break;
                    
                case 'nickname':
                    $columnMap['nickname'] = $index;
                    break;
                    
                case 'first_name':
                case 'firstname':
                    $columnMap['first_name'] = $index;
                    break;
                    
                case 'last_name':
                case 'lastname':
                case 'surname':
                    $columnMap['last_name'] = $index;
                    break;
                    
                case 'phone':
                case 'telephone':
                case 'phone_number':
                case 'mobile':
                    $columnMap['phone'] = $index;
                    break;
                    
                case 'address':
                case 'street':
                case 'address_1':
                case 'billing_address_1':
                    $columnMap['address'] = $index;
                    break;
                    
                case 'city':
                case 'billing_city':
                    $columnMap['city'] = $index;
                    break;
                    
                case 'state':
                case 'province':
                case 'billing_state':
                    $columnMap['state'] = $index;
                    break;
                    
                case 'country':
                case 'billing_country':
                    $columnMap['country'] = $index;
                    break;
                    
                case 'zip':
                case 'postcode':
                case 'zip_code':
                case 'billing_postcode':
                    $columnMap['zip'] = $index;
                    break;
            }
        }
        
        return $columnMap;
    }

    /**
     * Update an existing customer
     */
    protected function updateExistingCustomer(Customer $customer, array $data): bool
    {
        try {
            // Update basic customer data
            $customer->name = $this->buildCustomerName($data) ?: $customer->name;
            $customer->phone = $data['phone'] ?? $customer->phone;
            
            // Handle password update if provided
            if (!empty($data['password'])) {
                if ($this->isWordPressPassword($data['password'])) {
                    $this->info("Found WordPress password format for {$data['email']}");
                    // Make sure we don't double-prefix the password
                    $wpPassword = $this->convertWordPressPassword($data['password']);
                    // Check if the password already has a wp: prefix
                    if (Str::startsWith($wpPassword, 'wp:') && Str::startsWith($data['password'], '$2y$')) {
                        $this->info("Preventing double prefix for bcrypt password");
                        $customer->password = $data['password']; // Store bcrypt directly
                    } else {
                        $customer->password = $wpPassword;
                    }
                    $this->info("Updated password: " . substr($customer->password, 0, 10) . "...");
                } else {
                    $this->info("Using standard password format for {$data['email']}");
                    $customer->password = Hash::make($data['password']);
                }
                $customer->save();
                return true;
            }
            
            $this->info("No password provided for customer: {$data['email']}");
            return false;
        } catch (\Exception $e) {
            $this->error("Error updating customer: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new customer
     */
    protected function createNewCustomer(array $data): bool
    {
        try {
            // Prepare customer data
            $customerData = [
                'name' => $this->buildCustomerName($data),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ];
            
            $this->info("Creating customer with name: {$customerData['name']}");
            
            // Handle password
            if (!empty($data['password'])) {
                if ($this->isWordPressPassword($data['password'])) {
                    $this->info("Found WordPress password format for {$data['email']}");
                    // Make sure we don't double-prefix the password
                    $wpPassword = $this->convertWordPressPassword($data['password']);
                    // Check if the password already has a wp: prefix
                    if (Str::startsWith($wpPassword, 'wp:') && Str::startsWith($data['password'], '$2y$')) {
                        $this->info("Preventing double prefix for bcrypt password");
                        $customerData['password'] = $data['password']; // Store bcrypt directly
                    } else {
                        $customerData['password'] = $wpPassword;
                    }
                    $this->info("Converted password: " . substr($customerData['password'], 0, 10) . "...");
                } else {
                    $this->info("Using standard password format for {$data['email']}");
                    $customerData['password'] = Hash::make($data['password']);
                }
            } else {
                // Generate random password if none provided
                $this->info("Generating random password for {$data['email']}");
                $customerData['password'] = Hash::make(Str::random(12));
            }
            
            // Create customer
            $customer = new Customer();
            $customer->fill($customerData);
            $customer->save();
            
            // Add address if available
            if (!empty($data['address']) || !empty($data['city']) || !empty($data['state'])) {
                $addressData = [
                    'name' => $customerData['name'],
                    'phone' => $customerData['phone'],
                    'email' => $customerData['email'],
                    'address' => $data['address'] ?? '',
                    'city' => $data['city'] ?? '',
                    'state' => $data['state'] ?? '',
                    'country' => $data['country'] ?? '',
                    'zip_code' => $data['zip'] ?? '',
                    'customer_id' => $customer->id,
                    'is_default' => 1,
                ];
                
                $address = new Address();
                $address->fill($addressData);
                $address->save();
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("Error creating customer: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a password is a WordPress hashed password
     */
    protected function isWordPressPassword(string $password): bool
    {
        // WordPress passwords typically start with $P$, $H$, or $2y$
        $result = Str::startsWith($password, ['$P$', '$H$', '$2y$']);
        $this->info("Password check result: " . ($result ? 'true' : 'false') . " for password starting with: " . substr($password, 0, 4));
        return $result;
    }

    /**
     * Convert WordPress password hash to Laravel compatible format if needed
     * Note: This is a simplified approach - WordPress uses PHPass which may not be directly compatible
     */
    protected function convertWordPressPassword(string $wpPassword): string
    {
        // If it's already a bcrypt hash (starts with $2y$), we can use it directly
        if (Str::startsWith($wpPassword, '$2y$')) {
            $this->info("Using bcrypt hash directly");
            return $wpPassword;
        }
        
        // For other WordPress hash formats, we need to use a special flag to indicate
        // this is a WordPress password that needs special handling during login
        $this->info("Adding wp: prefix to password hash");
        
        // Log more detailed information about the hash
        $this->info("WordPress hash format: " . substr($wpPassword, 0, 4) . ", length: " . strlen($wpPassword));
        
        return 'wp:' . $wpPassword;
    }

    /**
     * Build customer name from available fields
     */
    protected function buildCustomerName(array $data): string
    {
        // Priority order for name fields
        if (!empty($data['name'])) {
            return $data['name'];
        }
        
        if (!empty($data['display_name'])) {
            return $data['display_name'];
        }
        
        if (!empty($data['first_name']) && !empty($data['last_name'])) {
            return $data['first_name'] . ' ' . $data['last_name'];
        }
        
        if (!empty($data['first_name'])) {
            return $data['first_name'];
        }
        
        if (!empty($data['last_name'])) {
            return $data['last_name'];
        }
        
        if (!empty($data['user_login'])) {
            return $data['user_login'];
        }
        
        if (!empty($data['nickname'])) {
            return $data['nickname'];
        }
        
        // If no name fields are available, use part of the email
        if (!empty($data['email'])) {
            $emailParts = explode('@', $data['email']);
            return ucfirst(str_replace(['.', '_', '-'], ' ', $emailParts[0]));
        }
        
        return 'Customer';
    }
}
