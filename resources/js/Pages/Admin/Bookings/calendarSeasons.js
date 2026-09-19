export const calendarSeasonForDate = (seasons, selectedSeason, date) => {
    const matches = seasons.filter(s => s.start_date <= date && s.end_date >= date);
    if (selectedSeason) {
        return matches.find(s => Number(s.id) === Number(selectedSeason.id)) || matches[0] || null;
    }

    const publicMatches = matches.filter(s => !s.internal_only);
    if (publicMatches.length) return publicMatches[0];

    // If this service has a normal season timeline, a gap in that timeline stays unavailable.
    if (seasons.some(s => !s.internal_only)) return null;

    return matches[0] || null;
};

export const calendarAnchorSeasonForDate = (seasons, date) => {
    const withInventory = seasons.filter(s => s.inventory);
    if (!withInventory.length) return null;

    const normalSeasons = withInventory.filter(s => !s.internal_only);
    const anchors = normalSeasons.length ? normalSeasons : withInventory;
    const directMatch = anchors
        .filter(s => s.start_date <= date && s.end_date >= date)
        .sort((a, b) => b.start_date.localeCompare(a.start_date))[0];
    if (directMatch) return directMatch;

    const previous = anchors
        .filter(s => s.end_date < date)
        .sort((a, b) => b.end_date.localeCompare(a.end_date))[0];
    if (previous) return previous;

    return anchors
        .filter(s => s.start_date > date)
        .sort((a, b) => a.start_date.localeCompare(b.start_date))[0] || null;
};

export const calendarSeasonsForRange = (seasons, selectedSeason, start, end) => {
    const withInventory = seasons.filter(s => s.inventory);
    if (!selectedSeason?.inventory) return [];

    const timeline = [selectedSeason];
    const used = new Set([Number(selectedSeason.id)]);

    let first = selectedSeason;
    while (first.start_date > start) {
        const previous = withInventory
            .filter(s => !used.has(Number(s.id)) && s.end_date < first.start_date)
            .sort((a, b) => b.end_date.localeCompare(a.end_date))[0];
        if (!previous) break;
        timeline.unshift(previous);
        used.add(Number(previous.id));
        first = previous;
    }

    let last = selectedSeason;
    while (last.end_date < end) {
        const next = withInventory
            .filter(s => !used.has(Number(s.id)) && s.start_date > last.end_date)
            .sort((a, b) => a.start_date.localeCompare(b.start_date))[0];
        if (!next) break;
        timeline.push(next);
        used.add(Number(next.id));
        last = next;
    }

    return timeline.filter(s => s.start_date <= end && s.end_date >= start);
};

export const mergeSeasonCalendars = (responses, days, selectedSeason) => {
    const rows = new Map();
    const dates = new Map();
    const owners = responses.map(response => response.season);

    for (const { season, calendar } of responses) {
        for (const row of calendar.schedule || []) {
            if (!rows.has(row.time)) rows.set(row.time, { time: row.time, slot_id: row.slot_id, days: {} });
            for (const [label, cell] of Object.entries(row.days || {})) {
                if (cell?.date) dates.set(label, cell.date);
                if (Number(calendarSeasonForDate(owners, selectedSeason, cell?.date)?.id) === Number(season.id)) {
                    rows.get(row.time).days[label] = {
                        ...cell,
                        season_id: season.id,
                        slot_id: row.slot_id,
                    };
                }
            }
        }
    }

    return {
        days,
        schedule: [...rows.values()].sort((a, b) => a.time.localeCompare(b.time)).map(row => ({
            ...row,
            days: Object.fromEntries(days.map(label => [label, row.days[label] || {
                date: dates.get(label),
                slots: 0,
                status: 'unavailable',
                note: 'Outside season or no time slot',
            }])),
        })),
        message: '',
    };
};
