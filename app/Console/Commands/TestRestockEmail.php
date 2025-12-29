<?php

namespace App\Console\Commands;

use App\Mail\RestockRequestMail;
use App\Models\Part;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestRestockEmail extends Command
{
    protected $signature = 'test:restock-email {partId}';
    protected $description = 'Test sending restock email for a part';

    public function handle()
    {
        $part = Part::find($this->argument('partId'));
        
        if (!$part) {
            $this->error('Part not found!');
            return 1;
        }

        $supplier = Supplier::where('name', $part->supplier)->first();
        
        if (!$supplier) {
            $this->error('Supplier not found!');
            return 1;
        }

        $requestedQuantity = max(1, ($part->reorder_point * 2) - $part->in_stock);

        $this->info("Sending email to: {$supplier->email}");
        $this->info("Part: {$part->name}");
        $this->info("Requested Quantity: {$requestedQuantity}");

        try {
            Mail::to($supplier->email)->send(new RestockRequestMail($part, $supplier, $requestedQuantity));
            $this->info('✓ Email sent successfully!');
            $this->info('Check Mailpit at http://localhost:8025');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
            return 1;
        }
    }
}
