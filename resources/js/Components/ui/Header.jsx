// components/ui/Header.tsx
import { Link } from '@inertiajs/react';

export default function Header({ auth }) {
  return (
    // <header className="bg-white shadow-md">
    //   <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    //     {/* Logo */}
    //     <Link href="/" className="flex items-center space-x-2">
    //       <img src="/images/logo.png" alt="Stormsriver Adventures Logo" className="h-8" />
    //       <span className="font-semibold text-lg text-gray-800">Stormsriver Adventures</span>
    //     </Link>

    //     {/* Navigation */}
    //     <nav className="space-x-6 text-sm text-gray-600">
    //       <Link href="/" className="hover:text-blue-600">Home</Link>
    //       {/* <Link href="/services" className="hover:text-blue-600">Services</Link>
    //       <Link href="/gift-vouchers" className="hover:text-blue-600">Gift Vouchers</Link> */}
    //       <Link href="/cart" className="hover:text-blue-600">Cart</Link>
    //     </nav>
    //   </div>
    // </header>

    <header className="bg-gray-50 sticky top-0 z-50">
      <div className="max-w-screen-xl mx-auto px-4 pt-12 flex justify-between items-center">
        <Link href="/" className="text-xl font-bold text-blue-700">
          <img src="/images/logo.png" alt="Stormriver Adventures" className="h-10" />
        </Link>
        <nav className="flex gap-6 text-sm text-gray-700">
          <Link href="/" className="hover:text-blue-600">Home</Link>
          <Link href="/" className="hover:text-blue-600">Services</Link>
          <Link href="/cart" className="hover:text-blue-600">Cart {auth?.count}</Link>
          {/* <Link href="/questionnaire" className="hover:text-blue-600">Questionnaire</Link> */}
          <Link href="/login" className="hover:text-blue-600">Login</Link>
        </nav>
      </div>
    </header>

  );
}
