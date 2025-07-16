<?php

namespace Botble\Ecommerce\Commands;

use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Address;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateCustomerAddressesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:update-customer-addresses {file : The CSV file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update customer addresses from WordPress CSV export';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting customer address update...');

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
        
        if (!isset($columns['email'])) {
            $this->error("CSV file must contain 'email' column");
            fclose($handle);
            return 1;
        }

        $this->info('Processing customer addresses...');
        $bar = $this->output->createProgressBar(count(file($file)) - 1);
        $bar->start();

        $updated = 0;
        $created = 0;
        $skipped = 0;
        $notFound = 0;

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
                
                // Skip if no email
                if (empty($customerData['email'])) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Find customer by email
                $customer = Customer::where('email', $customerData['email'])->first();
                
                if (!$customer) {
                    $this->info("\nCustomer not found: {$customerData['email']}");
                    $notFound++;
                    $bar->advance();
                    continue;
                }
                
                // Check if we have address data
                if (empty($customerData['address']) && empty($customerData['city']) && 
                    empty($customerData['state']) && empty($customerData['country']) && 
                    empty($customerData['zip'])) {
                    $this->info("\nNo address data found for: {$customerData['email']}");
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Check if customer already has an address
                $address = Address::where('customer_id', $customer->id)->first();
                
                // Prepare address data
                $addressData = [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customerData['address'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'state' => $customerData['state'] ?? '',
                    'country' => $customerData['country'] ?? '',
                    'zip_code' => $customerData['zip'] ?? '',
                    'customer_id' => $customer->id,
                    'is_default' => 1,
                ];
                
                if ($address) {
                    // Update existing address
                    $address->fill($addressData);
                    $address->save();
                    $this->info("\nUpdated address for {$customerData['email']}");
                    $updated++;
                } else {
                    // Create new address
                    $address = new Address();
                    $address->fill($addressData);
                    $address->save();
                    $this->info("\nCreated address for {$customerData['email']}");
                    $created++;
                }
                
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            
            $this->info("\nAddress update completed: {$updated} updated, {$created} created, {$skipped} skipped, {$notFound} not found.");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error updating addresses: " . $e->getMessage());
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
            'address' => null,
            'address_1' => null,
            'address_2' => null,
            'billing_address_1' => null,
            'billing_address_2' => null,
            'shipping_address_1' => null,
            'shipping_address_2' => null,
            'city' => null,
            'billing_city' => null,
            'shipping_city' => null,
            'state' => null,
            'billing_state' => null,
            'shipping_state' => null,
            'country' => null,
            'billing_country' => null,
            'shipping_country' => null,
            'zip' => null,
            'postcode' => null,
            'billing_postcode' => null,
            'shipping_postcode' => null,
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
                    
                case 'address':
                case 'street':
                case 'street_address':
                    $columnMap['address'] = $index;
                    break;
                    
                case 'address_1':
                case 'address1':
                    $columnMap['address'] = $index;
                    break;
                    
                case 'billing_address_1':
                case 'billing_address1':
                    $columnMap['address'] = $index;
                    break;
                    
                case 'shipping_address_1':
                case 'shipping_address1':
                    $columnMap['address'] = $index;
                    break;
                    
                case 'city':
                    $columnMap['city'] = $index;
                    break;
                    
                case 'billing_city':
                    $columnMap['city'] = $index;
                    break;
                    
                case 'shipping_city':
                    $columnMap['city'] = $index;
                    break;
                    
                case 'state':
                case 'province':
                    $columnMap['state'] = $index;
                    break;
                    
                case 'billing_state':
                case 'billing_province':
                    $columnMap['state'] = $index;
                    break;
                    
                case 'shipping_state':
                case 'shipping_province':
                    $columnMap['state'] = $index;
                    break;
                    
                case 'country':
                    $columnMap['country'] = $index;
                    break;
                    
                case 'billing_country':
                    $columnMap['country'] = $index;
                    break;
                    
                case 'shipping_country':
                    $columnMap['country'] = $index;
                    break;
                    
                case 'zip':
                case 'zip_code':
                case 'postal_code':
                case 'postcode':
                    $columnMap['zip'] = $index;
                    break;
                    
                case 'billing_postcode':
                case 'billing_zip':
                case 'billing_zip_code':
                    $columnMap['zip'] = $index;
                    break;
                    
                case 'shipping_postcode':
                case 'shipping_zip':
                case 'shipping_zip_code':
                    $columnMap['zip'] = $index;
                    break;
            }
        }
        
        return $columnMap;
    }
}
