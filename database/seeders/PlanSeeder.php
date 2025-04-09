<?php
namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userPlans = [
            [
                'uuid'              => Str::uuid()->toString(),
                'stripe_price_id'   => config('services.stripe.subscription.medium.price_id'),
                'stripe_product_id' => config('services.stripe.subscription.medium.product_id'),
                'name'              => 'Start Your 3-day Free trail to continue',
                'price'             => 10,
                'type'              => 'medium',
                'status'            => 'active',
                'created_at'        => date("Y-m-d H:i:s"),
                'updated_at'        => date("Y-m-d H:i:s"),
            ],
            [
                'uuid'              => Str::uuid()->toString(),
                'stripe_price_id'   => config('services.stripe.subscription.premium.price_id'),
                'stripe_product_id' => config('services.stripe.subscription.premium.product_id'),
                'name'              => 'Start Your 3-day Free trail to continue',
                'price'             => 30,
                'type'              => 'premium',
                'status'            => 'active',
                'created_at'        => date("Y-m-d H:i:s"),
                'updated_at'        => date("Y-m-d H:i:s"),
            ],
        ];

        if (! empty($userPlans)) {
            Plan::insert($userPlans);
        }
    }
}
