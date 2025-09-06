## Relevant Files

- `includes/class-photobooster-ai.php` - Main plugin bootstrap; ensure loader hooks are available for admin.
- `includes/class-photobooster-ai-loader.php` - Registers actions/filters; wire up admin enqueues and REST scaffold.
- `admin/class-photobooster-ai-admin.php` - Admin hooks; register/enqueue admin assets, localize data/nonces.
- `admin/js/photobooster-ai-admin.js` - Admin JS entry; may host/dispatch media modal bootstrap logic.
- `admin/js/media-enhance-bootstrap.js` - Media modal observer; injects AI Enhance button; lazy-loads React bundle with attachment props.
- `admin/css/photobooster-ai-admin.css` - WordPress admin-style button CSS; ensures no layout shifts.
- `admin/react-app/` - New. Vite + React + TypeScript source for the app shell.
  - `admin/react-app/tsconfig.json` - TypeScript compiler configuration for the app.
  - `admin/react-app/vite.config.js` - Vite config outputting to `admin/dist` with `manifest: true`.
  - `admin/react-app/src/main.tsx` - TypeScript entry; mounts the React app.
  - `admin/react-app/src/mount.tsx` - Mountable entry exporting mount/unmount for lazy-loading.
  - `admin/react-app/src/App.tsx` - React overlay component with attachment preview, enhancement UI, accessibility features.
  - `admin/react-app/.gitignore` - Ignore node_modules, dist, and other local files.
- `admin/dist/` - Built assets output (JS/CSS) and `manifest.json` for URL resolution.
- `includes/class-photobooster-ai-rest.php` - REST API with security scaffolding, permission callbacks, and attachment validation utilities.
- `uninstall.php` - Confirm no changes required.

### Notes

- Unit tests (if added) should live next to the file (e.g., `media-enhance-bootstrap.test.js`).
- Ensure asset URLs are pulled from Vite `manifest.json` for cache-busting.
- Follow WP capability checks and prepare nonce utilities even if v1 performs no writes.

## Tasks

- [x] 1.0 Scaffold Vite + React + TypeScript app under `admin/react-app/` and outputs to `admin/dist/`
  - [x] 1.1 Create `admin/react-app/` and initialize `package.json` with React, ReactDOM, TypeScript, Vite.
  - [x] 1.2 Add `tsconfig.json` and `vite.config.ts` (set `build.outDir` to `../dist` and `manifest: true`).
  - [x] 1.3 Create `src/main.tsx`, `src/App.tsx`, and basic CSS; render a minimal shell component.
  - [x] 1.4 Add NPM scripts: `dev`, `build`.
  - [x] 1.5 Verify build outputs hashed assets and `manifest.json` into `admin/dist/`.
  - [x] 1.6 Add `.gitignore` file with all the non-versioned files(like `node_modules`).

- [x] 2.0 Add PHP helpers to resolve Vite manifest assets and register admin handles
  - [x] 2.1 Implement helper to read `admin/dist/manifest.json` and resolve entry JS/CSS URLs.
  - [x] 2.2 Register a lightweight admin bootstrap script and localize data (manifest map, REST base, nonce, capability flags).
  - [x] 2.3 Ensure helpers only expose data to users with `upload_files`.

- [x] 3.0 Implement media modal bootstrap to inject "✨ AI Enhance" button into `.attachment-info`
  - [x] 3.1 Enqueue bootstrap only on Media screens (`upload.php`, `media-new.php`).
  - [x] 3.2 Detect Media Modal render via `wp.media` frame events and/or `MutationObserver` on `.media-modal`.
  - [x] 3.3 On selection change, if the selected attachment MIME is `image/jpeg`, `image/png`, or `image/webp`, show the button.
  - [x] 3.4 Inject the button at the top of `.attachment-info`; dedupe with a marker attribute (e.g., `data-pbai-enhance="1"]).
  - [x] 3.5 Hide/remove the button for ineligible selections and when the modal closes.

- [x] 4.0 Implement lazy-loading to import built React bundle and mount overlay container
  - [x] 4.1 On click, dynamically import the app entry using the URL from the Vite manifest (no global enqueue).
  - [x] 4.2 Create and append a mount node `#pbai-enhance-root` inside the media modal content.
  - [x] 4.3 Mount the React app and pass attachment props (ID, preview URL) to the shell.
  - [x] 4.4 Implement cleanup: unmount React, remove container, restore focus to the trigger button, handle repeated opens.

- [x] 5.0 Build React app shell with attachment preview, placeholder controls, and close behavior
  - [x] 5.1 Implement overlay layout (header with title and Close, content with preview area).
  - [x] 5.2 Display selected attachment preview (thumbnail/full if available); handle missing preview gracefully.
  - [x] 5.3 Implement Close button and onClose callback to unmount/cleanup.
  - [x] 5.4 Add ESC key handler and a basic focus trap within the overlay.

- [x] 6.0 Add security scaffolding: capability gating (`upload_files`), nonce plumbing, REST route no-op
  - [x] 6.1 In PHP, localize a nonce and REST namespace/base (`photobooster-ai/v1`) for future calls.
  - [x] 6.2 Register a no-op REST route (e.g., `/noop`) for connectivity checks; return `{ ok: true }`.
  - [x] 6.3 Use a permission callback that requires `upload_files` and verifies the nonce.
  - [x] 6.4 Prepare utility functions for future write operations (validate attachment ownership, file type checks).

- [x] 7.0 Add minimal CSS and ensure overlay accessibility basics (Esc close, focus)
  - [x] 7.1 Style the injected button to match WP admin buttons; ensure it doesn't shift layout.
  - [x] 7.2 Style the overlay container (z-index, backdrop, responsive sizing within the modal).
  - [x] 7.3 Set `role="dialog"`, `aria-modal="true"`, and label the dialog for screen readers; verify keyboard navigation.


