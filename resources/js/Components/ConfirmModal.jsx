// resources/js/Components/ConfirmModal.jsx
import { useState } from 'react';
import { Dialog } from '@headlessui/react';

export default function ConfirmModal({ title = "Are you sure?", message, confirmText = "Confirm", cancelText = "Cancel", onConfirm, onClose }) {
    const [open, setOpen] = useState(true);

    const handleConfirm = () => {
        setOpen(false);
        onConfirm();
        if (onClose) {
            onClose();
        }
    };

    return (
        <Dialog
            open={open}
            onClose={() => {
                setOpen(false);
                if (onClose) {
                    onClose();
                }
            }}
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <Dialog.Overlay className="fixed inset-0 bg-black/40" />
            <div className="relative bg-white rounded-2xl shadow-lg p-6 w-full max-w-sm text-center">
                <Dialog.Title className="text-lg font-semibold mb-2">{title}</Dialog.Title>
                {message && <p className="text-gray-600 mb-6">{message}</p>}
                <div className="flex justify-center gap-3">
                    <button
                        onClick={() => {
                            setOpen(false);
                            if (onClose) {
                                onClose();
                            }
                        }}
                        className="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition"
                    >
                        {cancelText}
                    </button>
                    <button
                        onClick={handleConfirm}
                        className="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition"
                    >
                        {confirmText}
                    </button>
                </div>
            </div>
        </Dialog>
    );
}
