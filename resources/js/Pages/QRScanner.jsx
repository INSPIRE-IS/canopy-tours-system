// resources/js/Pages/QRScanner.jsx
import React, { useEffect } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';

export default function QRScanner() {
  useEffect(() => {
    const scanner = new Html5QrcodeScanner(
      'qr-reader',
      { fps: 10, qrbox: { width: 250, height: 250 } },
      false
    );

    scanner.render(
      (decodedText, decodedResult) => {
        console.log('QR Code Scanned:', decodedText);
        // Optionally redirect or fill a form field
        alert(`Booking ID: ${decodedText}`);
      },
      (errorMessage) => {
        // console.warn(`Scan error: ${errorMessage}`);
      }
    );

    return () => scanner.clear();
  }, []);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 p-4">
      <div className="bg-white rounded-md shadow-md p-6 w-full max-w-md">
        <h2 className="text-lg font-semibold mb-4 text-center">Scan Booking QR Code</h2>
        <div id="qr-reader" className="w-full" />
      </div>
    </div>
  );
}
