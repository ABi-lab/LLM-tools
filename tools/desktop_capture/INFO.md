# Desktop Capture

## Name
Desktop Capture

## Key
desktop_capture

## Short description
Captures a screenshot of the entire virtual screen and returns it as a base64-encoded PNG with a stable local URL.

## Long description
Uses PowerShell to capture the full virtual screen (all monitors), stores the PNG in the tool service's `images/` directory, and returns a JSON object with the image URL and base64 data. The result is automatically forwarded to the model as an image, not as text. Use this tool when you need to see what is currently displayed on screen.

## Input parameters
(none)

## Output
JSON: {"_pd_image":true,"url":"http://localhost:7001/images/<id>.png","mime":"image/png","base64":"<base64-encoded PNG>"}
