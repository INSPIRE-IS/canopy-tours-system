<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\GiftVoucher;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sessionId = session()->getId() ?? null;
        logger()->info('sessionId inertia ' . $sessionId);
        $user = $request->user();
        $teams = Team::all();
        $unreadCount = 0;
        $alertCount = 0;
        $topics = [];

        if ($user) {
            $user->load([
                'roles.permissions',
                'team',
                'alerts' => fn($query) => $query
                    ->wherePivot('complete', false)
                    ->latest()
                    ->limit(20),
            ]);
            $permissions = $user->roles
                ->flatMap(fn($role) => $role->permissions->pluck('name'))
                ->unique()
                ->mapWithKeys(fn($name) => [$name => true])
                ->all();
            $teams = Team::all();
            $unreadCount = $user->unread_topic_count;
            $alertCount = $user->alerts()
                ->wherePivot('complete', false)
                ->count();
            $topics = $user->unreadTopics()->with('messages.sender')->get();

            //dd($teams);
            //dd($unreadCount);
        }

        if ($sessionId) {
            $booking = Booking::where('session_id', $sessionId)->where('status_payment_id', 1)->first();
            $bookingId = $booking?->id;
            if (!$booking) {
                $voucher = GiftVoucher::where('session_id', $sessionId)->where('status_payment_id', 1)->first();
                $customer = collect([
                    'full_name'     => $voucher?->full_name,
                    'email_address' => $voucher?->email_address,
                    'contact_number' => $voucher?->contact_number,
                ]);
                $count = $voucher?->gift_voucher_items?->count();
                $bookingId = $voucher?->booking_id;
            } else {
                $customer = Customer::find($booking?->customer_id);
                $count = $booking?->booking_items?->count();
            }
        }

        //dd($permissions);
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'teams' => $teams ?? [],
                'customer' => $customer,
                'count' => $count ?? 0,
                'bookingId' => $bookingId ?? null,
                'unreadCount' => $unreadCount,
                'alertCount' => $alertCount,
                'topics' => $topics,
            ],
            'permissions' =>  $permissions ?? [],
            'currentRoutePath' => $request->path(),
            'translations' => __('sidebar'), // this comes from the lang folder in resources
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'old' => fn() => $request->session()->getOldInput(),
        ];
    }
}
