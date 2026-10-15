import React from 'react';
import { Head } from '@inertiajs/react';
import { TenantImage } from './BookingComponents';
import SitePickerModal from './SitePickerModal';
import './booking-v2.css';

export default function SiteSelect({ teams = [] }) {
    return (
        <div className="kct-book-page kct-site-select-page">
            <Head title="Choose a booking site" />
            <div className="kct-app-shell">
                <header className="kct-header">
                    <span className="kct-header-logo" aria-hidden="true">
                        <TenantImage placeholderClassName="kct-image-placeholder--logo" />
                    </span>
                </header>
                <main className="kct-site-select-main" aria-hidden="true" />
                <SitePickerModal teams={teams} isOpen required />
            </div>
        </div>
    );
}
