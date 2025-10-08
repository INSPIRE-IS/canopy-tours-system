import { icons } from '@/icons';
import React, { useEffect, useRef } from 'react';

export default function CustomerToolsModal({ index, customer, position, onClose, CustomerToolClick }) {
    const modalRef = useRef();
    console.log('-----customer-----', customer);

    // Close when clicking outside the modal
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (modalRef.current && !modalRef.current.contains(event.target)) {
                onClose();
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [modalRef, onClose]);

    if (!customer || !position) return null;

    return (
        <div
            ref={modalRef}
            className="absolute z-50 w-[220px] bg-white rounded-xl shadow-lg border border-gray-200"
            style={{
                top: position.top,
                left: position.left,
            }}
        >
            <div className="p-4">
                <div className="flex justify-between items-center mb-2">
                    <h3 className="text-tool-card-title">Customer Tools</h3>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                </div>

                <div className="flex flex-col gap-2">
                    <button type='button' onClick={() => CustomerToolClick('check_in', index, customer)} className="btn-tool">{icons.check_in} Check-In</button>
                    <button type='button' onClick={() => CustomerToolClick('customer_invoice', index, customer)} className="btn-tool">{icons.customer_invoice} Customer Invoice</button>
                    <button type='button' onClick={() => CustomerToolClick('resend_invoice', index, customer)} className="btn-tool">{icons.resend_invoice} Resend Invoice</button>
                    <button type='button' onClick={() => CustomerToolClick('manifest', index, customer)} className="btn-tool">{icons.manifest} Manifest</button>
                    {/* <button type='button' onClick={() => CustomerToolClick('customer_invoice', index, customer)} className="btn-tool">{icons.customer_invoice} Customer Invoice</button>
                     <button type='button' onClick={() => CustomerToolClick('print_ticket', index, customer)} className="btn-tool">{icons.print_ticket} Print Ticket</button>
                    <button type='button' onClick={() => CustomerToolClick('voucher', index, customer)} className="btn-tool">{icons.voucher} Voucher</button> */}
                </div>
            </div>
        </div>
    );
}
