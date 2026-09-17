# Mandatory change review

Apply this gate before changing booking, payment, invoice, report, customer, or other persisted business behavior. It is an internal work step, not a request for the user to review every line.

1. State the business meaning and units of each affected field (for example, people count versus money). Do not infer allowed values from a database type, current cast, or input type.
2. Trace the field from input and validation through normalization, storage, edits, and every relevant display, document, payment, and report. Check existing data and backward compatibility before tightening rules.
3. Identify an ordinary case, an invalid case, and an existing-record case that could break. For financial or legal flows, also check that totals and documents still agree.
4. If the business meaning, rounding, or user-facing wording is uncertain, ask one focused question before changing dependent behavior. Continue independent investigation while waiting. Do not silently choose a rule.
5. Verify the scenarios at the relevant layer. A build or syntax check only proves compilation; it does not establish correct business behavior. State clearly when browser, database, or end-to-end verification was unavailable.
6. Before finishing, review the diff for scope creep and unintended files. Report the behavior changed, the evidence from tests, and any remaining risk. Do not describe an unverified change as safe to deploy.

For visual changes, also follow `docs/ui-conventions.md` and inspect the nearest existing screen and component before introducing styling.
