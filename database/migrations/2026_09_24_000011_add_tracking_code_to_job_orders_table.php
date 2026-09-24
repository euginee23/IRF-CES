<?php

use App\Services\TrackingCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A short code the customer can actually use.
     *
     * Tracking already worked, but only with the full job order number
     * (JO-20260924-0001) or a 64-character portal token — neither of which
     * survives being read out over the phone. This is the code that goes on
     * the claim stub and into the intake text message.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('job_orders', 'tracking_code')) {
            Schema::table('job_orders', function (Blueprint $table) {
                $table->char('tracking_code', TrackingCode::LENGTH)->nullable()->unique()->after('job_order_number');
            });
        }

        // Existing repairs get one too, so the counter can quote a code for
        // anything still open. Only rows that have none, so re-running this
        // cannot reissue a code a customer is already holding.
        $used = [];

        DB::table('job_orders')->whereNull('tracking_code')->select('id')->orderBy('id')->chunkById(200, function ($jobOrders) use (&$used) {
            foreach ($jobOrders as $jobOrder) {
                do {
                    $code = TrackingCode::random();
                } while (isset($used[$code]) || DB::table('job_orders')->where('tracking_code', $code)->exists());

                $used[$code] = true;

                DB::table('job_orders')->where('id', $jobOrder->id)->update(['tracking_code' => $code]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn('tracking_code');
        });
    }
};
