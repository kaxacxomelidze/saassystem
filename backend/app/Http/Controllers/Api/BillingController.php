<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;
use Stripe\Webhook;

class BillingController extends Controller
{
    public function checkout(Request $request)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');
        $request->validate(['plan' => ['required', 'in:starter,pro']]);

        $workspace = Workspace::findOrFail($workspaceId);
        $priceId = $request->plan === 'starter' ? env('STRIPE_PRICE_STARTER') : env('STRIPE_PRICE_PRO');

        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $session = CheckoutSession::create([
            'mode' => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'success_url' => env('FRONTEND_URL').'/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => env('FRONTEND_URL').'/billing/cancel',
            'metadata' => [
                'workspace_id' => (string) $workspace->id,
                'plan' => $request->plan,
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function webhook(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) env('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\Throwable) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $workspaceId = (int) ($session->metadata->workspace_id ?? 0);
            $plan = $session->metadata->plan ?? null;

            if ($workspaceId && $plan) {
                $workspace = Workspace::find($workspaceId);
                if ($workspace) {
                    $workspace->stripe_customer_id = $session->customer ?? null;
                    $workspace->stripe_subscription_id = $session->subscription ?? null;
                    $workspace->plan = $plan;
                    $workspace->billing_status = 'active';
                    $workspace->save();
                }
            }
        }

        return response('ok', 200);
    }
}
