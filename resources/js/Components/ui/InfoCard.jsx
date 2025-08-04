// components/ui/InfoCard.tsx
import React from 'react';
import RichTextDisplay from '../RichTextDisplay';

// type InfoCardProps = {
//   title: string;
//   content: React.ReactNode;
//   color?: 'green' | 'blue' | 'gray';
// };: InfoCardProps

export default function InfoCard({ title, content, color = 'gray' }) {
  const borderColor = {
    green: 'border-green-500 text-green-700',
    blue: 'border-blue-500 text-blue-700',
    gray: 'border-gray-300 text-gray-800',
  }[color];

  return (
    <div className="bg-white border rounded-md shadow-sm p-4">
      <h3 className={`text-md font-semibold mb-2 border-b pb-1 ${borderColor}`}>
        {title}
      </h3>
      <div className="text-sm leading-relaxed text-gray-700 space-y-2">
        <RichTextDisplay content={ content } />
      </div>
    </div>
  );
}
