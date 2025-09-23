<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otps:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired OTPs';

    /**
     * Execute the console command.
     */


    public function handle()
    {
        $deleted = Otp::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired OTP(s).");
        return 0;
    }
}
