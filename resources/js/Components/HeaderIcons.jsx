import { useState } from "react";
//import { motion, AnimatePresence } from "framer-motion";
import { icons } from '@/icons';

export default function HeaderIcons({ unreadCount, unreadTopics, user }) {
    const [viewMessages, setViewMessages] = useState(false);
    const [viewAlerts, setViewAlerts] = useState(false);

    const alertCount = user?.alerts?.length || 0;

    return (
        <div className="flex items-center gap-4 relative">

            {/* MESSAGES */}
            <div
                className="relative"
                onMouseEnter={() => setViewMessages(true)}
                onMouseLeave={() => setViewMessages(false)}
            >
                {icons.messages}
                {unreadCount > 0 && (
                    <span className="absolute -top-1 -right-4 bg-primary-500 text-black text-xs font-semibold w-5 h-5 flex items-center justify-center rounded-full shadow-md">
                        {unreadCount}
                    </span>
                )}

                {/* Hover modal */}
                <div>
                    {viewMessages && (
                        <div
                            initial={{ opacity: 0, y: -5 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -5 }}
                            className="absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 overflow-hidden"
                        >
                            <div className="p-3 border-b font-semibold text-gray-700">Messages</div>
                            <div className="max-h-64 overflow-y-auto">
                                {unreadTopics?.length > 0 ? (
                                    unreadTopics.map(topic => (
                                        <a
                                            key={topic.id}
                                            href={`admin/messages/${topic.id}`}
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            {topic.subject}
                                        </a>
                                    ))
                                ) : (
                                    <div className="px-4 py-3 text-sm text-gray-500">No new messages</div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* ALERTS */}
            <div
                className="relative"
                onClick={() => setViewAlerts(!viewAlerts)}
            // onClickCapture={() => setHovered("alerts")}
            >
                {icons.notifications}
                {alertCount > 0 && (
                    <span className="absolute -top-1 -right-4 bg-primary-500 text-black text-xs font-semibold w-5 h-5 flex items-center justify-center rounded-full shadow-md">
                        {alertCount}
                    </span>
                )}

                {/* Hover modal */}
                <div>
                    {viewAlerts && (
                        <div
                            initial={{ opacity: 0, y: -5 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -5 }}
                            className="absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 overflow-hidden"
                        >
                            {/* <div className="p-3 border-b font-semibold text-gray-700">Alerts</div> */}
                            <div className="flex justify-between items-center mb-2">
                                <h3 className="text-tool-card-title">Alerts</h3>
                                <button onClick={() => setViewAlerts(!viewAlerts)} className="text-gray-400 hover:text-gray-600 text-sm">{icons.close}</button>
                            </div>

                            <div className="max-h-64 overflow-y-auto">
                                {user?.alerts?.length > 0 ? (
                                    user.alerts.map(alert => (
                                        <a
                                            key={alert.id}
                                            href={alert.alert_link}
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            {alert.alert_text}
                                        </a>
                                    ))
                                ) : (
                                    <div className="px-4 py-3 text-sm text-gray-500">No new alerts</div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
