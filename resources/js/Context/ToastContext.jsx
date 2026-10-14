import React, { createContext, useContext, useEffect, useRef, useState, useCallback } from "react";

const ToastContext = createContext();
export const useToast = () => useContext(ToastContext);
// console.log('useToast', useToast);
const toastColors = {
    success: "bg-green-100 border-green-500 text-green-800",
    error: "bg-red-100 border-red-500 text-red-800",
    warning: "bg-amber-100 border-amber-500 text-amber-800",
    info: "bg-blue-100 border-blue-500 text-blue-800",
};

export const ToastProvider = ({ children }) => {
    const [toasts, setToasts] = useState([]);
    const nextToastId = useRef(0);
    const timers = useRef(new Map());

    const dismissToast = useCallback((id) => {
        const timer = timers.current.get(id);
        if (timer) clearTimeout(timer);
        timers.current.delete(id);
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    }, []);

    const showToast = useCallback((message, type = "info", duration = 3000) => {
        nextToastId.current += 1;
        const id = `${Date.now()}-${nextToastId.current}`;
        setToasts((prev) => [...prev, { id, message, type }]);

        if (duration > 0) {
            timers.current.set(id, setTimeout(() => dismissToast(id), duration));
        }

        return id;
    }, [dismissToast]);

    useEffect(() => () => {
        timers.current.forEach((timer) => clearTimeout(timer));
        timers.current.clear();
    }, []);

    return (
        <ToastContext.Provider value={{ showToast }}>
            {children}
            <div className="fixed top-4 right-4 left-4 sm:left-auto z-50 space-y-2" aria-live="polite" aria-atomic="false">

                {toasts.map((toast) => (
                    <div
                        key={toast.id}
                        role={toast.type === "error" ? "alert" : "status"}
                        className={`max-w-sm ml-auto border-l-4 rounded-lg shadow-lg p-4 flex items-start gap-3 ${toastColors[toast.type] || toastColors.info}`}
                    >
                        <p className="font-medium flex-1">{toast.message}</p>
                        <button
                            type="button"
                            onClick={() => dismissToast(toast.id)}
                            className="shrink-0 -mt-1 -mr-1 p-1 rounded hover:bg-black/10 focus:outline-none focus:ring-2 focus:ring-current"
                            aria-label="Dismiss notification"
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M4 4l8 8m0-8-8 8" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                            </svg>
                        </button>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
};
