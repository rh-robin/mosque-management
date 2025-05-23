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
        'password',
        'phone',
        'country',
        'city',
        'state',
        'address',
        'imam_name',
        'documents',
        'contact_person_phone',
        'role',
        'email_verified_at',
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



    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function communityPosts()
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function reacts()
    {
        return $this->hasMany(React::class);
    }

    // Method to handle subscription update
    /*public function updateStripeSubscription($invoice)
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

    }*/
}
