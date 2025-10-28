// components/ui/Header.tsx
import { Link } from '@inertiajs/react';

export default function TopNav({ auth, team }) {
  return (
    <header className="bg-gray-50 sticky top-0 z-50">
      <div className="max-w-screen-xl mx-auto px-4 pt-12 flex justify-between items-center">
        <Link href="/" className="text-xl font-bold text-blue-700">
          {/* <img src="/images/logo.png" alt="Stormriver Adventures" className="h-10" /> */}
          {team?.file_logo?.path != null ? (
            <img
              src={`${team?.file_logo?.path}`}
              alt="Canopy Tours"
              className="h-10"
            />
          ) : (
            <img
              src="/images/logo.png"
              alt="Canopy Tours"
              className="h-10"
            />
          )}


        </Link>
        <nav className="flex gap-6 text-sm text-gray-700">
          <Link href="/" className="hover:text-blue-600">Home</Link>
          <Link href="/" className="hover:text-blue-600">Services</Link>
          {auth?.bookingId && <Link href={route('front.payment.booking', { booking: auth?.bookingId })} className="hover:text-blue-600">Cart {auth?.count > 0 ? '(' + auth?.count + ')' : ''}</Link>}
          {/* <Link href="/questionnaire" className="hover:text-blue-600">Questionnaire</Link> */}
          <Link href="/login" className="hover:text-blue-600">Login</Link>
        </nav>
      </div>
    </header>

  );
}
