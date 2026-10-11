import React from 'react';
import { Head, Link } from '@inertiajs/react';
import TopNav from '@/Components/TopNav';

export default function PayfastResult({ auth, status, message }) {
  const isSuccess = status === 'success';

  return (
    <div className="bg-gray-50 min-h-screen">
      <Head title="Payment Status" />
      <TopNav auth={auth} />

      <div className="max-w-xl mx-auto p-6 text-center">
        <h1 className="text-2xl font-bold mb-4">
          {isSuccess ? 'Payment received' : 'Payment cancelled'}
        </h1>
        <p className="mb-6">{message}</p>
        <Link
          href={route('front.legacy.welcome')}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Back to home
        </Link>
      </div>
    </div>
  );
}
