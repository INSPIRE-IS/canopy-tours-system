import React, { useRef } from 'react';
import SignatureCanvas from 'react-signature-canvas';

export default function SignaturePad({ value, onChange }) {
  const sigPad = useRef(null);

  const clear = () => {
    sigPad.current.clear();
    onChange('');
  };

  const save = () => {
    const data = sigPad.current.toDataURL('image/png');
    onChange(data);
  };

  return (
    <div className="space-y-2">
      <div className="border border-gray-300 rounded-md">
        <SignatureCanvas
          ref={sigPad}
          penColor="black"
          canvasProps={{ className: 'w-full h-48 bg-white rounded-md' }}
        />
      </div>
      <div className="flex gap-2">
        {/* <button type="button" onClick={clear} className="px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600">
          Clear
        </button>
        <button type="button" onClick={save} className="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
          Save
        </button> */}

        <button type='button' onClick={clear} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-red-gradient-overlay border-[#C65468]  text-white cursor-pointer">
        <span className="text-btn-outline-gray">Clear</span>

        </button>
        <button type='button' onClick={save} className="flex items-center justify-center w-[40%] sm:w-[169px] h-[40px] gap-2 px-4 py-2 rounded-xl border-[3px] bg-green-gradient-overlay border-[#31B700] text-white shadow-inset-white cursor-pointer">
        <span className="text-btn-outline-gray">Save</span>
        </button>

      </div>
      {value && (
        <div>
          <p className="text-sm text-gray-500">Preview:</p>
          <img src={value} alt="Signature preview" className="border w-64 h-auto" />
        </div>
      )}
    </div>
  );
}
