## Introduction/Overview

Add an “✨ AI Enhance” button inside the WordPress Media Library modal’s attachment details panel (`.attachment-info`) to launch a lazy‑loaded React application (bundled build) within the admin. The initial release (v1) is an app shell that opens within the media modal without invoking any AI processing yet. The feature targets the `photobooster-ai` plugin and only appears for image attachments.

### Goal

Enable content editors to click “✨ AI Enhance” on eligible images inside the Media Library modal and open a React UI overlay within the modal, without page reloads, and with assets loaded on demand.

## Goals

- Render an “✨ AI Enhance” button in `.attachment-info` only in the Media Library modal and only for image attachments.
- On click, load the built React app (Vite build) on demand and render it in an overlay within the existing media modal context.
- Do not reload the page; mount/unmount React cleanly.
- Respect WordPress capabilities: require `upload_files`.
- Prepare a secure foundation (nonces/capability checks) for future write operations, though v1 performs no writes.

## User Stories

- As a content editor, I want to see an “✨ AI Enhance” button for image attachments in the Media Library modal so that I can access enhancement tools without leaving the modal.
- As a content editor, I want the enhancement UI to load quickly and only when needed so that normal admin performance is unaffected.
- As a site admin, I want the feature to respect user capabilities (`upload_files`) so that only authorized users can access it.

## Functional Requirements

1. Button Injection and Visibility
   1.1. The system must inject a button labeled “✨ AI Enhance” into the `.attachment-info` panel when an attachment is selected in the Media Library modal.
   1.2. The button must appear only in Media Library contexts (grid/list, modal opened from media screens) and not elsewhere (e.g., post editor media picker in v1).
   1.3. The button must display only for image attachments: `image/jpeg`, `image/png`, `image/webp`.
   1.4. The button placement must be at the top area of `.attachment-info` (before other actions/fields).

2. React App Launch (Shell Only in v1)
   2.1. Clicking the button must open a React-based overlay mounted within the media modal DOM (no page reloads).
   2.2. The React app for v1 is a shell: show selected attachment preview (thumbnail/full as available) and placeholder controls (no AI processing).
   2.3. Provide Close/Back control to return to the attachment details without leaving the modal.

3. Asset Loading and Build
   3.1. The React app must be built with Vite + React + TypeScript.
   3.2. Source directory: `admin/react-app/`; build output: `admin/dist/`.
   3.3. Assets must be lazy‑loaded only when the media modal opens and an eligible attachment is selected (no global admin enqueue).
   3.4. Use the Vite manifest to resolve built asset URLs. Ensure correct cache-busting via file hashes.

4. Admin Integration and DOM Strategy
   4.1. Hook into media modal lifecycle to detect selection changes and `.attachment-info` re-renders (may use `wp.media` events and/or `MutationObserver`).
   4.2. Ensure the button is injected once per render and deduplicated across re-renders.
   4.3. Create a deterministic container element for mounting the React overlay (e.g., `#pbai-enhance-root`) within the modal.

5. Permissions and Security
   5.1. Feature availability requires `upload_files` capability.
   5.2. Prepare nonce creation/verification utilities for future write actions (no writes in v1), to be used with WordPress REST API routes.

6. Backend/API (Scaffold Only in v1)
   6.1. Register a namespaced WordPress REST API route (disabled/no-op in v1) under the plugin namespace for future enhancement actions.
   6.2. Ensure route registration checks capabilities and nonces for any future write operation.

7. UX, UI, and States
   7.1. UI framework: Plain React + minimal CSS scoped to the app container to avoid collisions.
   7.2. Accessibility (basic): keyboard focus moves into the overlay; Escape closes; ARIA roles applied at a minimal level for dialogs.
   7.3. Error/empty states: display inline admin-style notices inside the overlay for missing selection or preview failures.

## Non-Goals (Out of Scope)

- Post editor or other media modal contexts beyond the Media Library in v1.
- Non-image attachments.
- Actual AI processing in v1 (no image modification, storage, or replacement flows).
- Internationalization for v1 (strings not yet translated).
- Analytics/telemetry.
- Using Gutenberg component library; use plain React + minimal CSS instead.

## Design Considerations (Optional)

- Keep the overlay visually lightweight and consistent with WordPress admin styles without relying on Gutenberg packages.
- Place the button at the top of `.attachment-info` to maximize visibility, with icon/text label: “✨ AI Enhance”.
- Respect the modal’s scroll and sizing; the overlay should adapt to the modal’s dimensions and not overflow the viewport.

## Technical Considerations (Optional)

- Enqueue a small bootstrap script on media screens that:
  - Listens for the media modal opening and attachment selection changes.
  - Injects the button into `.attachment-info` when conditions match.
  - On click, dynamically imports the Vite-built React bundle from `admin/dist/` using the manifest to resolve URLs, then mounts the app.
- Use `admin_enqueue_scripts` to register scripts/styles but avoid enqueuing the heavy bundle until needed.
- Namespace DOM hooks and CSS with a `pbai-` prefix to avoid collisions.
- Ensure idempotency across modal re-renders; remove stale containers before mounting.
- Prepare REST route scaffold in the plugin for future versions (e.g., `/photobooster-ai/v1/enhance`). Keep handlers as no-op in v1.

## Success Metrics

- Manual verification that clicking the button loads the React app within the media modal without a full page reload (Acceptance choice 20.B).
- No asset loading on admin pages where the media modal is never opened.
- Button never appears for non-image attachments.

## Open Questions

1. Should the overlay dim or replace `.attachment-info`, or present as a slide-in panel? (default: full overlay panel within modal)
2. Preferred thumbnail vs. full-size preview behavior for very large images?
3. Any branding or color guidelines to apply to the button and overlay?
4. For v2, when enabling processing, should results create a new attachment (selected in 7.A) with linkage to the original via post meta?

## Implementation Notes (Informative)

- Directory structure (proposed):
  - `admin/react-app/` — Vite + React + TS source.
  - `admin/dist/` — built assets + `manifest.json`.
  - `admin/js/` — small bootstrap script that manages button injection and dynamic import.
- Capability check: gating logic before rendering/injecting the button.
- Nonce: generate and localize a nonce for future authenticated calls; not used for writes in v1.


