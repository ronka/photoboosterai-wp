# Reference Image — Backend API Specification

## Feature Overview

The `POST /api/generate-image` endpoint now accepts an **optional** `reference_image` file field.
When present, the AI should use the reference image as a visual style and composition guide
(e.g., via ControlNet, IP-Adapter, or a similar conditioning technique) alongside the primary
product photo.

Generation without a reference image must continue to work exactly as before
(full backward compatibility).

---

## Request Format

**Method:** `POST`
**Content-Type:** `multipart/form-data`
**Authentication:** `Authorization: Bearer <api_key>`

### Fields

| Field | Type | Required | Description |
|---|---|---|---|
| `image` | file | **Yes** | The product photo to enhance (seed image). JPEG, PNG, or WebP. |
| `prompt` | string | **Yes** | Text prompt describing the desired output style/scene. |
| `reference_image` | file | No | Optional style/composition reference image. JPEG, PNG, or WebP, max 10 MB. |

### Field constraints

- **`image`** — JPEG / PNG / WebP; no explicit size limit enforced server-side by the plugin, but keep under 20 MB for practical reasons.
- **`reference_image`** — JPEG / PNG / WebP; **max 10 MB**. Omit the field entirely if no reference image is needed.

---

## Expected Behavior

- **Without `reference_image`**: behavior is identical to the existing implementation — the AI generates an enhanced photo based solely on `image` + `prompt`.
- **With `reference_image`**: the AI uses the reference image as an additional conditioning signal to guide style, color palette, lighting, or composition. The exact conditioning mechanism (ControlNet, IP-Adapter, style-transfer, etc.) is left to the backend implementation team.

---

## Response Format

The response format is **unchanged**.

```json
{
  "success": true,
  "image": "<base64-encoded PNG/JPEG string>"
}
```

On error:

```json
{
  "success": false,
  "error": "Human-readable error message"
}
```

---

## Example cURL Request

### Without reference image (existing behavior)

```bash
curl -X POST https://api.photoboosterai.com/api/generate-image \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "image=@/path/to/product.jpg" \
  -F "prompt=Transform the product into a professional studio photo on a pure seamless white background."
```

### With reference image (new optional field)

```bash
curl -X POST https://api.photoboosterai.com/api/generate-image \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "image=@/path/to/product.jpg" \
  -F "prompt=Transform the product into a professional studio photo on a pure seamless white background." \
  -F "reference_image=@/path/to/reference.jpg"
```

---

## Backward Compatibility

- The `reference_image` field is **entirely optional**.
- If the field is absent from the request, the endpoint must behave identically to the current implementation.
- No changes to the response schema are required.

---

## Validation (enforced by the WordPress plugin proxy)

The WordPress plugin validates the uploaded reference image before forwarding it to this endpoint:

- Accepted MIME types: `image/jpeg`, `image/png`, `image/webp`
- Maximum file size: **10 MB**
- Upload error code must be `UPLOAD_ERR_OK`
- File must pass `is_uploaded_file()` check

If any validation fails, the WordPress layer returns a `400` error to the browser and the request never reaches this endpoint.
