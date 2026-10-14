import React, { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

const logoFallback = '/images/kct-booking-v2/logo-karkloof.png';

export default function SitePickerModal({ teams = [], isOpen, onClose, required = false }) {
    const firstSiteRef = useRef(null);

    useEffect(() => {
        if (!isOpen) return undefined;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        firstSiteRef.current?.focus();

        const closeOnEscape = (event) => {
            if (event.key === 'Escape' && !required) onClose?.();
        };
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [isOpen, onClose, required]);

    if (!isOpen) return null;

    const selectSite = (teamId) => {
        window.localStorage.setItem('preferred_team_id', String(teamId));
        router.get(route('front.welcome', { team: teamId }));
    };

    return (
        <div
            className="kct-site-picker-backdrop"
            role="presentation"
            onMouseDown={() => { if (!required) onClose?.(); }}
        >
            <section
                className="kct-site-picker-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="site-picker-title"
                onMouseDown={(event) => event.stopPropagation()}
            >
                {!required && (
                    <button type="button" className="kct-site-picker-close" onClick={onClose} aria-label="Close site selection">
                        &times;
                    </button>
                )}
                <span className="kct-site-picker-eyebrow">Booking location</span>
                <h1 id="site-picker-title">Choose a site</h1>
                <p>Select the location you would like to book.</p>

                {teams.length > 0 ? (
                    <div className="kct-site-picker-options">
                        {teams.map((team, index) => (
                            <button
                                key={team.id}
                                ref={index === 0 ? firstSiteRef : null}
                                type="button"
                                className="kct-site-picker-option"
                                onClick={() => selectSite(team.id)}
                            >
                                <img
                                    src={team.logo || logoFallback}
                                    onError={(event) => { event.currentTarget.src = logoFallback; }}
                                    alt=""
                                />
                                <span>
                                    <strong>{team.name}</strong>
                                    {team.description && <small>{team.description}</small>}
                                </span>
                                <span className="kct-site-picker-arrow" aria-hidden="true">&rarr;</span>
                            </button>
                        ))}
                    </div>
                ) : (
                    <div className="kct-site-picker-empty">
                        <strong>No booking sites are available yet.</strong>
                        <span>Please check back shortly.</span>
                    </div>
                )}
            </section>
        </div>
    );
}
