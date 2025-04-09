<?php
namespace App\Http\Controllers\API;

use Stripe\Stripe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\SubscriptionPausedNotification;
use App\Notifications\SubscriptionResumedNotification;
use App\Notifications\SubscriptionCancelledNotification;
use App\Notifications\SubscriptionScheduleAbortedNotification;
use App\Notifications\SubscriptionScheduleCanceledNotification;

class WebHooksController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $payload        = $request->getContent();
        $sigHeader      = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            Log::info("Event type: " . $event);

            // Handle the event based on type
            switch ($event->type) {
                case 'invoice.payment_failed':
                    $this->handlePaymentFailure($event);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handlePaymentSuccess($event);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event);
                    break;

                case 'customer.subscription.paused':
                    $this->handleSubscriptionPaused($event);
                    break;

                case 'customer.subscription.resumed':
                    $this->handleSubscriptionResumed($event);
                    break;

                case 'subscription_schedule.aborted':
                    $this->handleSubscriptionScheduleAborted($event);
                    break;

                case 'subscription_schedule.canceled':
                    $this->handleSubscriptionScheduleCanceled($event);
                    break;
                default:
                    Log::info("Unhandled event type: " . $event->type);
                    break;
            }
            Log::info("Webhook handled");
            return response('Webhook handled', 200);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response('Webhook error: ' . $e->getMessage(), 400);
        }
    }

// Handle payment failure
    private function handlePaymentFailure($event)
    {
        $invoice = $event->data->object; // Contains the invoice object
        $user = User::where('stripe_id', $invoice->customer)->first();

        if ($user) {
            // Mark subscription as 'past_due' and notify the user
            $user->subscription('default')->update(['stripe_status' => 'past_due']);
            $user->notify(new PaymentFailedNotification());
        }
    }

    // Handle payment success
    private function handlePaymentSuccess($event)
    {
        $invoice = $event->data->object;
        
        $user = User::where('stripe_id', $invoice->customer)->first();

        if ($user) {
            // Create or Update Subscriptions
            $user->updateStripeSubscription($invoice);
        }else{
            Log::info("User not found");
        }
    }

    // Handle subscription deletion (cancellation)
    private function handleSubscriptionDeleted($event)
    {
        $subscription = $event->data->object; // Contains the subscription object
        $user = User::where('stripe_id', $subscription->customer)->first();

        if ($user) {
            // Cancel the subscription in the database
            $user->subscription('default')->cancel();
            $user->notify(new SubscriptionCancelledNotification());
        }
    }

    // Handle subscription pause
    private function handleSubscriptionPaused($event)
    {
        $subscription = $event->data->object; // Contains the subscription object
        $user = User::where('stripe_id', $subscription->customer)->first();

        if ($user) {
            // Mark the subscription as paused
            $user->subscription('default')->update(['stripe_status' => 'paused']);
            $user->notify(new SubscriptionPausedNotification());
        }
    }

    // Handle subscription resume after pause
    private function handleSubscriptionResumed($event)
    {
        $subscription = $event->data->object; // Contains the subscription object
        $user = User::where('stripe_id', $subscription->customer)->first();

        if ($user) {
            // Mark the subscription as active
            $user->subscription('default')->update(['stripe_status' => 'active']);
            $user->notify(new SubscriptionResumedNotification());
        }
    }

    // Handle subscription schedule aborted
    private function handleSubscriptionScheduleAborted($event)
    {
        $schedule = $event->data->object; // Contains the subscription schedule object
        $user = User::where('stripe_id', $schedule->customer)->first();

        if ($user) {
            // Handle aborted schedule, e.g., notify the user
            $user->notify(new SubscriptionScheduleAbortedNotification());
        }
    }

    // Handle subscription schedule cancellation
    private function handleSubscriptionScheduleCanceled($event)
    {
        $schedule = $event->data->object; // Contains the subscription schedule object
        $user = User::where('stripe_id', $schedule->customer)->first();

        if ($user) {
            // Handle canceled schedule, e.g., notify the user
            $user->notify(new SubscriptionScheduleCanceledNotification());
        }
    }

}
