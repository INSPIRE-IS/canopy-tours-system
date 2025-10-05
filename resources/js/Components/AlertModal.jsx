
import { icons } from '@/icons';

export default function AlertModal({ type = "success", title, message, onClose }) {

    const colors = {
        success: "border-green-500",
        warning: "border-yellow-500",
        danger: "border-red-500",
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div
                className={`bg-white rounded-2xl shadow-xl w-full max-w-md border-t-4 p-6 ${colors[type]}`}
            >
                <div className="flex items-start gap-3">
                    {type == "success" && <span className='flex items-center justify-center w-6 h-6'>{icons.closeCircle}</span>}
                    {type == "warning" && <span className='flex items-center justify-center w-6 h-6'>{icons.closeCircle}</span>}
                    {type == "danger" && <span className='flex items-center justify-center w-6 h-6'>{icons.closeCircle}</span>}
                    <div className="flex-1">
                        <h3 className="text-lg font-semibold">{title}</h3>
                        <p className="text-gray-600">{message}</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <span className='flex items-center justify-center w-6 h-6'>{icons.close}</span>
                    </button>
                </div>
            </div>
        </div>
    );
}


// setAlert({
//     title: "Success!",
//     message: "Your changes were saved successfully.",
//     type: "success",
// });
// setAlert({
//     title: "Warning!",
//     message: "Please check your inputs.",
//     type: "warning",
// });
// setAlert({
//     title: "Danger!",
//     message: "This action cannot be undone.",
//     type: "danger",
// });