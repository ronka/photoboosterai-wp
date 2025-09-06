# AI Enhance Button in WordPress Media Library Sidebar (PRD)

## Introduction/Overview
Add a "✨ AI Enhance" button to the WordPress Media Library modal and Upload.php screens. When clicked, it replaces the `.media-sidebar` with a simple form containing a single text input (autofocus) and a "Generate" button (no-op for now). A "Back" button restores the default sidebar content. This enables a future AI-assisted enhancement flow while keeping current behavior unchanged.

## Goals
1. Add a visible "✨ AI Enhance" button inside `.attachment-info` (Attachment Details area).
2. Show on Media Library (Upload.php) attachment details only (not in post editor modal).
3. On click, replace `.media-sidebar` with a form (input + Generate button + Back button).
4. Ensure the input is editable and focused on open; Generate does nothing.
5. Provide a Back button to restore the default sidebar.
6. Minimal styling; inherit WP admin styles.

## User Stories
- As a site user, I want to click an "✨ AI Enhance" button in the Media Library to open a form where I can type instructions so that I can later run AI enhancements.
- As a site user, I want a Back button to return to the Attachment Details panel so I can continue normal media editing.

## Functional Requirements
1. The system shall inject a button labeled "✨ AI Enhance" inside `.attachment-info` when viewing an attachment in the Media Library (Upload.php).
2. The system shall restrict visibility to logged-in users only (per selection: Everyone logged-in).
3. On button click, the system shall capture the current `.media-sidebar` HTML for restoration.
4. The system shall replace the content of `.media-sidebar` with a form containing:
   - A single-line text input with placeholder "Describe the enhancement…" and autofocus.
   - A primary button labeled "Generate"; it performs no action (no-op) for now.
   - A secondary button labeled "Back"; it restores the saved `.media-sidebar` HTML.
5. The system shall ensure the input is editable.
6. The system shall use minimal, native WordPress admin styles (button classes where appropriate) without custom CSS unless necessary.
7. The system shall not alter the Media Modal opened from editors; it only applies to the Media Library screen (Upload.php).
8. The system shall be implemented in `admin/js/photobooster-ai-admin.js` and enqueued via existing admin enqueue hooks.

## Non-Goals (Out of Scope)
- No AI calls or backend processing.
- No persistence of the entered text.
- No changes to permissions or capabilities beyond showing the button to any logged-in user.
- No custom styles beyond default admin UI classes.

## Design Considerations
- Placement: inside `.attachment-info`, near other action controls.
- Use emoji label exactly: "✨ AI Enhance".
- No i18n change request (do not add new translation scaffolding for this spike).
- Consider event delegation to handle dynamically rendered media sidebar content.

## Technical Considerations
- Use jQuery to interact with the Media Library DOM.
- Detect Media Library context by checking `pagenow === 'upload'` or presence of `.upload-php` body class.
- When replacing `.media-sidebar` contents, preserve a reference to restore on Back.
- Use WordPress core button classes (`button button-primary`, `button-secondary`).
- Avoid interfering with core listeners; scope selectors appropriately.

## Success Metrics
- Button appears on Media Library attachment details and not in post editor modal.
- Clicking button swaps `.media-sidebar` to the form; input is focused.
- Back button reliably restores the prior sidebar state.

## Open Questions
- Should we further limit visibility by capability (e.g., `upload_files`) despite selection D (everyone logged-in)?
- Should we persist the entered prompt for later use?
- Should the form appear as a section within the sidebar rather than full replacement for future iterations?
