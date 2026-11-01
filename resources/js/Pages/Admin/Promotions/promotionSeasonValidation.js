const isTruthy = (value) => value === true || value === 1 || value === '1';

export const normalizePromotionDate = (value) => String(value || '').slice(0, 10);

const containsDate = (season, date) => {
  const start = normalizePromotionDate(season?.start_date);
  const end = normalizePromotionDate(season?.end_date);

  return Boolean(start && end && date >= start && date <= end);
};

export function validatePromotionSeasonWindow({
  type,
  seasonId,
  seasons = [],
  activityStartDate,
  activityEndDate,
  internalUseOnly = false,
}) {
  const start = normalizePromotionDate(activityStartDate);
  const end = normalizePromotionDate(activityEndDate);
  if (!start || !end) {
    return { valid: true, message: '' };
  }

  const eligibleSeasons = seasons.filter(
    (season) => isTruthy(internalUseOnly) || !isTruthy(season?.internal_only),
  );

  if (type === 'time_slot') {
    const selectedSeason = eligibleSeasons.find((season) => String(season.id) === String(seasonId));
    const valid = selectedSeason
      && containsDate(selectedSeason, start)
      && containsDate(selectedSeason, end);

    return {
      valid: Boolean(valid),
      message: valid ? '' : 'Activity dates must fall within the selected season.',
    };
  }

  const startCovered = eligibleSeasons.some((season) => containsDate(season, start));
  const endCovered = eligibleSeasons.some((season) => containsDate(season, end));
  const valid = startCovered && endCovered;

  return {
    valid,
    message: valid
      ? ''
      : 'Activity start and end dates must each fall within an eligible season for the selected service.',
  };
}
