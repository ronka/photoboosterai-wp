## Relevant Files

- `admin/js/photobooster-ai-admin.js` - Admin JS entry; implement DOM logic for button injection and sidebar swap.
- `admin/class-photobooster-ai-admin.php` - Enqueues admin JS; optionally gate by `$hook` if ever needed.
- `includes/class-photobooster-ai.php` - Defines admin hooks; no code changes expected.

### Notes

- Use jQuery and event delegation/MutationObserver to handle dynamic media modal DOM.
- Follow PRD: no i18n additions, minimal styling; use WordPress core button classes.
- Target Media Library (`upload.php`) only; avoid affecting editor-opened media modals.

## Tasks

- [x] 1.0 Scope script to Media Library (`upload.php`) only; exclude editor media modal.
  - [x] 1.1 In JS, bail early unless `window.pagenow === 'upload'` or `body.hasClass('upload-php')`.
  - [x] 1.2 Wrap logic in a DOM-ready handler to avoid running before elements exist.
  - [x] 1.3 Keep all listeners and observers unregistered when not on `upload.php`.
- [x] 2.0 Inject "✨ AI Enhance" button into `.attachment-info` on attachment selection.
  - [x] 2.1 Observe `.attachments-browser` for appearance/updates of `.attachment-details .attachment-info`.
  - [x] 2.2 Append a single button element labeled "✨ AI Enhance" with `button` class; avoid duplicates.
  - [x] 2.3 Place button near existing actions, preserving core layout.
- [x] 3.0 Swap `.media-sidebar` with AI form on click; capture original markup.
  - [x] 3.1 On button click, cache current `.media-sidebar` HTML string for restoration.
  - [x] 3.2 Replace `.media-sidebar` contents with a form: text input (placeholder "Describe the enhancement…", autofocus) and buttons.
  - [x] 3.3 Use WordPress classes: `button button-primary` for Generate; `button button-secondary` for Back.
  - [x] 3.4 Focus the input after injecting the form.
- [x] 4.0 Implement Back to restore saved sidebar reliably.
  - [x] 4.1 Delegate Back click handling from `.media-sidebar` to capture dynamically inserted button.
  - [x] 4.2 Restore cached sidebar HTML and clear the cache variable.
  - [x] 4.3 Ensure repeated open/close cycles work without memory leaks or duplicate handlers.
- [x] 5.0 Autofocus and keep input editable; `Generate` is a no-op.
  - [x] 5.1 Prevent default on form submit and on Generate button click.
  - [x] 5.2 Ensure input is enabled and focusable; call `.focus()` after render.
  - [x] 5.3 Do not send network requests or persist input (explicit no-op).
- [x] 6.0 Use core admin button classes; avoid custom CSS.
  - [x] 6.1 Use `button button-primary` for Generate and `button button-secondary` for Back.
  - [x] 6.2 Do not add CSS files; rely on native admin styles for layout.

