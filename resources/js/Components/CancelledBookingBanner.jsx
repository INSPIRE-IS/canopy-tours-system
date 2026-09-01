export default function CancelledBookingBanner({ message = 'This booking is cancelled. You are viewing historical details only.' }) {
    return (
        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span className="font-semibold">Cancelled booking.</span> {message}
        </div>
    );
}
