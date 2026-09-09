<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingItem;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class CheckinKioskController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Gate::allows('checkins_kiosk') || Gate::allows('bookings_access'), 403);

        $teamId = (int) auth()->user()->team_id;
        $date = $request->input('date') ?: now()->toDateString();
        $search = trim((string) $request->input('search', ''));

        $bookingItems = BookingItem::query()
            ->with([
                'booking:id,reference,lead_name,customer_id,team_id',
                'service:id,name',
                'season:id,name',
                'booking_inventory.inventory_time_slot',
                'customers:id,full_name,email_address,contact_number,parent_id',
                'customer_manifests:id,booking_item_id,customer_id,is_indemnity,attendance_status,type,guardian_name',
            ])
            ->where('team_id', $teamId)
            ->whereHas('booking_inventory', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('booking', function ($bookingQuery) use ($search) {
                            $bookingQuery
                                ->where('reference', 'like', "%{$search}%")
                                ->orWhere('lead_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customers', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email_address', 'like', "%{$search}%")
                                ->orWhere('contact_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('service', function ($serviceQuery) use ($search) {
                            $serviceQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->get()
            ->sortBy(function (BookingItem $bookingItem) {
                return sprintf(
                    '%s %s %s',
                    $bookingItem->booking_inventory?->getRawOriginal('date') ?? '',
                    $bookingItem->booking_inventory?->inventory_time_slot?->start_time ?? '',
                    $bookingItem->booking?->reference ?? ''
                );
            })
            ->values();

        $items = $bookingItems->map(fn (BookingItem $bookingItem) => $this->formatBookingItem($bookingItem))->values();

        return Inertia::render('Admin/Checkins/Kiosk', [
            'date' => $date,
            'search' => $search,
            'items' => $items,
        ]);
    }

    private function formatBookingItem(BookingItem $bookingItem): array
    {
        $expectedCount = max(0, (int) $bookingItem->pax_adults + (int) $bookingItem->pax_children);
        $completedCount = $bookingItem->customer_manifests
            ->where('is_indemnity', true)
            ->count();

        return [
            'booking_id' => $bookingItem->booking_id,
            'booking_item_id' => $bookingItem->id,
            'reference' => $bookingItem->booking?->reference,
            'lead_name' => $bookingItem->booking?->lead_name,
            'service_name' => $bookingItem->service?->name,
            'season_name' => $bookingItem->season?->name,
            'activity_date' => $bookingItem->booking_inventory?->getRawOriginal('date'),
            'start_time' => $bookingItem->booking_inventory?->inventory_time_slot?->start_time,
            'expected_count' => $expectedCount,
            'completed_count' => $completedCount,
            'status' => $this->statusLabel($expectedCount, $completedCount),
            'checkin_url' => $this->signedCheckinLink($bookingItem),
            'guests' => $this->formatGuests($bookingItem),
        ];
    }

    private function formatGuests(BookingItem $bookingItem): array
    {
        $customers = $bookingItem->customers->values();
        $manifests = $bookingItem->customer_manifests->keyBy('customer_id');
        $expectedCount = max(0, (int) $bookingItem->pax_adults + (int) $bookingItem->pax_children);

        $guests = $customers
            ->map(function (Customer $customer) use ($bookingItem, $manifests) {
                $manifest = $manifests->get($customer->id);

                return [
                    'customer_id' => $customer->id,
                    'name' => $customer->full_name ?: 'Unnamed guest',
                    'email' => $customer->email_address,
                    'phone' => $customer->contact_number,
                    'completed' => (bool) $manifest?->is_indemnity,
                    'attendance_status' => $manifest?->attendance_status,
                    'checkin_url' => $this->signedCheckinLink($bookingItem, $customer),
                ];
            })
            ->values();

        $remaining = max(0, $expectedCount - $guests->count());
        for ($i = 1; $i <= $remaining; $i++) {
            $guests->push([
                'customer_id' => null,
                'name' => "Guest {$i}",
                'email' => null,
                'phone' => null,
                'completed' => false,
                'attendance_status' => null,
                'checkin_url' => $this->signedCheckinLink($bookingItem),
            ]);
        }

        return $guests->values()->all();
    }

    private function statusLabel(int $expectedCount, int $completedCount): string
    {
        if ($expectedCount > 0 && $completedCount >= $expectedCount) {
            return 'complete';
        }

        if ($completedCount > 0) {
            return 'partial';
        }

        return 'pending';
    }

    private function signedCheckinLink(BookingItem $bookingItem, ?Customer $customer = null): string
    {
        $bookingInventory = $bookingItem->booking_inventory;
        $bookingDate = $bookingInventory?->getRawOriginal('date') ?? $bookingInventory?->date;
        $startTime = $bookingInventory?->inventory_time_slot?->start_time;
        $expiresAt = now()->addDays(7);

        if ($bookingDate && $startTime) {
            try {
                $activityStart = Carbon::parse(trim("{$bookingDate} {$startTime}"), config('app.timezone'));
                if ($activityStart->isFuture()) {
                    $expiresAt = $activityStart;
                }
            } catch (\Throwable) {
                $expiresAt = now()->addDays(7);
            }
        }

        return URL::temporarySignedRoute(
            'front.booking-questionnaire.checkin',
            $expiresAt,
            array_filter([
                'reference' => $bookingItem->booking?->reference,
                'customer' => $customer?->id,
                'booking_item_id' => $bookingItem->id,
                'return_to' => 'kiosk',
            ], static fn ($value) => $value !== null && $value !== '')
        );
    }
}
