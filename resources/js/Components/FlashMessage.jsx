import { useEffect, useState } from "react";

export default function FlashMessage({ message, type = "success" }) {
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        const timer = setTimeout(() => setVisible(false), 3000);
        return () => clearTimeout(timer);
    }, []);

    if (!message || !visible) return null;

    return (
        <div
            className={`fixed top-4 right-4 px-4 py-2 rounded text-white shadow-lg ${
                type === "success" ? "bg-green-500" : "bg-red-500"
            }`}
        >
            {message}
        </div>
    );
}
