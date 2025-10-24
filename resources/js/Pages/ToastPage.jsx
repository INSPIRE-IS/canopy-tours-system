import { useToast } from "@/Components/ToastContext";

export default function ExamplePage() {
    const { showToast } = useToast();

    return (
        <div className="p-8 space-x-2">
            <button
                onClick={() => showToast("Saved successfully!", "success")}
                className="px-4 py-2 bg-green-600 text-white rounded-lg"
            >
                Success
            </button>
            <button
                onClick={() => showToast("Something went wrong!", "error")}
                className="px-4 py-2 bg-red-600 text-white rounded-lg"
            >
                Error
            </button>
            <button
                onClick={() => showToast("Be careful, unsaved changes!", "warning")}
                className="px-4 py-2 bg-amber-600 text-white rounded-lg"
            >
                Warning
            </button>
            <button
                onClick={() => showToast("New update available!", "info")}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg"
            >
                Info
            </button>
        </div>
    );
}
