// resources/js/Components/ConfirmModal.jsx
import { useState } from 'react';
import { Dialog } from '@headlessui/react';

export default function ConfirmModal({
    title = "Are you sure?",
    message,
    confirmText = "Confirm",
    cancelText = "Cancel",
    onConfirm,
    onClose,
    requireConfirmationCheckbox = false,
    confirmationLabel = "I understand and want to continue.",
}) {
    const [open, setOpen] = useState(true);
    const [isConfirmed, setIsConfirmed] = useState(false);

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
                {requireConfirmationCheckbox && (
                    <label className="mb-6 flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-left text-sm text-amber-800">
                        <input
                            type="checkbox"
                            checked={isConfirmed}
                            onChange={(event) => setIsConfirmed(event.target.checked)}
                            className="mt-1"
                        />
                        <span>{confirmationLabel}</span>
                    </label>
                )}
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
                        disabled={requireConfirmationCheckbox && !isConfirmed}
                        className="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {confirmText}
                    </button>
                </div>
            </div>
        </Dialog>
    );
}
