<?php

namespace Botble\Ecommerce\Commands;

use Botble\Ecommerce\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixWordPressPasswordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:fix-wp-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix WordPress passwords that may have double prefixes or other issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting WordPress password fix...');

        // Find customers with WordPress passwords
        $customers = Customer::where('password', 'like', 'wp:%')
            ->orWhere('password', 'like', '$wp%')
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No WordPress passwords found that need fixing.');
            return 0;
        }

        $this->info('Found ' . $customers->count() . ' customers with WordPress passwords that may need fixing.');
        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        $fixed = 0;
        $skipped = 0;

        DB::beginTransaction();
        
        try {
            foreach ($customers as $customer) {
                $originalPassword = $customer->password;
                $this->info("\nChecking password for customer: {$customer->email}");
                
                // Fix double-prefixed passwords (wp:$2y$)
                if (Str::startsWith($originalPassword, 'wp:$2y$')) {
                    $this->info("Found double-prefixed bcrypt password for {$customer->email}");
                    // Remove wp: prefix for bcrypt passwords
                    $customer->password = substr($originalPassword, 3);
                    $customer->save();
                    $fixed++;
                    $this->info("Fixed password for {$customer->email}");
                }
                // Fix passwords with $wp prefix instead of wp:
                else if (Str::startsWith($originalPassword, '$wp')) {
                    $this->info("Found malformed WordPress password prefix for {$customer->email}");
                    // Replace $wp with wp:
                    $customer->password = 'wp:' . substr($originalPassword, 3);
                    $customer->save();
                    $fixed++;
                    $this->info("Fixed password for {$customer->email}");
                }
                else {
                    $skipped++;
                    $this->info("No issues found with password for {$customer->email}");
                }
                
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            
            $this->info("\nPassword fix completed: {$fixed} fixed, {$skipped} skipped.");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error fixing passwords: " . $e->getMessage());
            return 1;
        }
    }
}
