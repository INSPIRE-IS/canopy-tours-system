import React, { createContext, useContext, useState, useCallback } from "react";

const ToastContext = createContext();
export const useToast = () => useContext(ToastContext);
console.log('useToast', useToast);
const toastColors = {
    success: "bg-green-100 border-green-500 text-green-800",
    error: "bg-red-100 border-red-500 text-red-800",
    warning: "bg-amber-100 border-amber-500 text-amber-800",
    info: "bg-blue-100 border-blue-500 text-blue-800",
};

export const ToastProvider = ({ children }) => {
    const [toasts, setToasts] = useState([]);

    const showToast = useCallback((message, type = "info", duration = 3000) => {
        const id = Date.now();
        setToasts((prev) => [...prev, { id, message, type }]);
        setTimeout(() => {
            setToasts((prev) => prev.filter((toast) => toast.id !== id));
        }, duration);
    }, []);

    return (
        <ToastContext.Provider value={{ showToast }}>
            {children}
            <div className="fixed top-4 right-4 z-50 space-y-2">

                {toasts.map((toast) => (
                    <div
                        key={toast.id}
                        className={`max-w-sm border-l-4 rounded-lg shadow-lg p-4 ${toastColors[toast.type]}`}
                    >
                        <p className="font-medium">{toast.message}</p>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
};
