# UI conventions for this app

Use this checklist when adding or changing admin or guest-facing UI. The app has several established page patterns; match the area being edited rather than applying one global layout to every screen.

1. Inspect the target page and its closest sibling (for example, Booking Create and Edit) before building a control. Keep their labels, spacing, type, colors, states, and responsive behavior consistent.
2. Search `resources/js/Components` before writing a new control. Admin forms already use components such as `CustomInputText`, `CustomSelect`, `CustomCheckbox`, `CustomTextarea`, and `PhoneInput`. Check each component's supported props and behavior before using it; do not assume it handles optional, disabled, or error states the way a new field needs.
3. Reuse the buttons, cards, modals, filters, tables, empty states, and feedback patterns used by that feature. For tables, first inspect the nearest comparable admin list; `BookingsTable` is a dashboard component, not a universal table standard. Prefer existing app classes in `resources/css/app.css` and the page's Tailwind patterns over new one-off styling.
4. Keep create, edit, view, and related list screens consistent. Preserve keyboard access, labels, validation feedback, loading/disabled states, and usable mobile layouts when adding controls.
5. If no existing component or nearby pattern fits, explain the gap and ask the user before creating a new shared component or visual pattern. A small page-local variation that follows the existing design does not require a new component.
6. Do not restyle unrelated UI as part of a functional change. If a component is unsuitable or broken, raise that explicitly instead of silently replacing its design across the app.
7. The admin area is designed for desktop use. Check new or changed layouts at common 13-inch and 15-inch laptop widths and a larger desktop width; controls and tables must not overlap, clip, or force horizontal scrolling within their cards. Tablet and phone layouts are outside the current admin design scope unless explicitly requested.
