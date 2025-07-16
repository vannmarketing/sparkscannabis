<?php

namespace Botble\Ecommerce\Commands;

use Botble\Ecommerce\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateCustomerNamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:update-customer-names {file : The CSV file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update customer names from WordPress CSV export';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting customer name update...');

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

        $this->info('Processing customer names...');
        $bar = $this->output->createProgressBar(count(file($file)) - 1);
        $bar->start();

        $updated = 0;
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
                
                // Build name from available fields
                $name = $this->buildCustomerName($customerData);
                
                if (empty($name)) {
                    $this->info("\nNo name data found for: {$customerData['email']}");
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Update customer name
                $customer->name = $name;
                $customer->save();
                
                $this->info("\nUpdated name for {$customerData['email']} to: {$name}");
                $updated++;
                
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            
            $this->info("\nName update completed: {$updated} updated, {$skipped} skipped, {$notFound} not found.");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error updating names: " . $e->getMessage());
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
            'name' => null,
            'first_name' => null,
            'last_name' => null,
            'display_name' => null,
            'user_login' => null,
            'nickname' => null,
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
                    
                case 'name':
                case 'display_name':
                    $columnMap['name'] = $index;
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
                    
                case 'user_login':
                case 'username':
                    $columnMap['user_login'] = $index;
                    break;
                    
                case 'nickname':
                    $columnMap['nickname'] = $index;
                    break;
                    
                case 'display_name':
                    $columnMap['display_name'] = $index;
                    break;
            }
        }
        
        return $columnMap;
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
        
        return '';
    }
}
