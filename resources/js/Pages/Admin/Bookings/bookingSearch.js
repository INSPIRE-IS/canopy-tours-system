export const phoneDigitsForSearch = (value) => {
    const term = String(value || '').trim();

    // Do not treat digits inside references, names, or email addresses as phone searches.
    if (!term || !/^[+\d\s().-]+$/.test(term)) return '';

    return term.replace(/\D/g, '');
};
