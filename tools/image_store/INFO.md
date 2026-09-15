# Image Store
## Name
Image Store
## Key
image_store
## Short description
Store a base64-encoded image on disk and return a stable local URL for serving it.
## Long description
Accepts a base64 image (JPEG, PNG, GIF, or WebP) and writes it to the tool service's `images/` directory under a random, non-guessable filename. Returns the served URL (e.g. `http://localhost:7001/images/<id>.jpg`) so the caller can pass it to endpoints that accept image URLs instead of inline base64.

Files persist until the user manually deletes them from the `images/` directory — there is no automatic cleanup or TTL. This tool is intended for use by Prompt Designer's URL-transport vision mode: the app calls `image_store` directly before dispatching a message to a URL-only endpoint, then substitutes the returned URL for the base64 data in the request body.
## Input parameters
- **image** (string, required): base64-encoded image data, without the `data:…;base64,` prefix
- **mime** (string, optional, default: image/jpeg): MIME type of the image — one of `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- **filename** (string, optional): original filename hint for debugging — not used in the stored path
## Output
Plain-text URL of the stored image, e.g. `http://localhost:7001/images/a3f2…c8.jpg`. On error, a plain-text message beginning with `Error:`.
