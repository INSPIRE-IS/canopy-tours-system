// components/ui/Header.tsx
import { Link } from '@inertiajs/react';

export default function Header() {
  return (
    <header className="bg-white shadow-md">
      <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        {/* Logo */}
        <Link href="/" className="flex items-center space-x-2">
          <img src="/images/logo.png" alt="Stormsriver Adventures Logo" className="h-8" />
          <span className="font-semibold text-lg text-gray-800">Stormsriver Adventures</span>
        </Link>

        {/* Navigation */}
        <nav className="space-x-6 text-sm text-gray-600">
          <Link href="/" className="hover:text-blue-600">Home</Link>
          <Link href="/services" className="hover:text-blue-600">Services</Link>
          <Link href="/gift-vouchers" className="hover:text-blue-600">Gift Vouchers</Link>
          <Link href="/cart" className="hover:text-blue-600">Cart</Link>
        </nav>
      </div>
    </header>
  );
}
