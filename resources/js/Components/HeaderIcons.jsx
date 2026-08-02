import { useEffect, useState } from "react";
//import { motion, AnimatePresence } from "framer-motion";
import { icons } from '@/icons';
import AlertContextModal from '@/Components/AlertContextModal';

export default function HeaderIcons({ unreadCount, unreadTopics, alertCount: totalAlertCount = 0, user }) {
    const [viewMessages, setViewMessages] = useState(false);
    const [viewAlerts, setViewAlerts] = useState(false);
    const [activeAlertId, setActiveAlertId] = useState(null);
    const [alerts, setAlerts] = useState(user?.alerts || []);
    const [alertCount, setAlertCount] = useState(totalAlertCount || user?.alerts?.length || 0);

    useEffect(() => {
        setAlerts(user?.alerts || []);
        setAlertCount(totalAlertCount || user?.alerts?.length || 0);
    }, [totalAlertCount, user?.alerts]);
    const openAlert = (alert) => {
        if (alert?.booking_id || alert?.customer_id || alert?.customer_manifest_id) {
            setActiveAlertId(alert.id);
            setViewAlerts(false);
            return;
        }

        window.location.href = safeLocalHref(alert?.alert_link);
    };
    const safeLocalHref = (href, fallback = "/admin/bookings") => {
        if (!href) return fallback;

        try {
            const url = new URL(href, window.location.origin);
            return `${url.pathname}${url.search}${url.hash}`;
        } catch {
            return href.startsWith("/") ? href : fallback;
        }
    };

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
                                            href={`/admin/messages/${topic.id}`}
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
                                {alertCount > alerts.length && (
                                    <div className="px-4 py-2 text-xs text-gray-500">
                                        Showing latest {alerts.length} of {alertCount} alerts
                                    </div>
                                )}
                                {alerts.length > 0 ? (
                                    alerts.map(alert => (
                                        <button
                                            key={alert.id}
                                            type="button"
                                            onClick={() => openAlert(alert)}
                                            className="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            {alert.alert_text}
                                        </button>
                                    ))
                                ) : (
                                    <div className="px-4 py-3 text-sm text-gray-500">No new alerts</div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
            <AlertContextModal
                alertId={activeAlertId}
                isOpen={Boolean(activeAlertId)}
                onClose={() => setActiveAlertId(null)}
                onResolved={(resolvedAlertId) => {
                    setAlerts((currentAlerts) => currentAlerts.filter((alert) => alert.id !== resolvedAlertId));
                    setAlertCount((currentCount) => Math.max(currentCount - 1, 0));
                }}
                reloadOnly={['auth']}
            />
        </div>
    );
}
