<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Billable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'provider',
        'provider_id',
        'password',
        'role',
        'email_verified_at',
        'selected_pet',
        'stripe_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function pets()
    {
        return $this->hasMany(Pet::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Method to handle subscription update
    public function updateStripeSubscription($invoice)
    {
        $plan = Plan::where('stripe_price_id', $invoice->lines->data[0]->price['id'])->first();

        if (! $plan) {
            Log::info("Plan not found");
        }
        try {
            $this->subscriptions()->updateOrCreate([
                'type' => $plan->type,
                'stripe_id'     => $invoice->customer,
                'stripe_price'  => $plan->stripe_price_id,
                'stripe_status' => 'active',
                'plan_id'       => $plan->id,
                'quantity'      => $invoice->lines->data[0]->quantity,
                'ends_at'       => $invoice->period_end,
            ]);

            Log::info("Subscription updated");

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

    }
}
