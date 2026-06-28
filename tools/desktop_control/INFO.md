# Desktop Control

## Name
Desktop Control

## Key
desktop_control

## Short description
Controls the mouse and keyboard at the OS level: move cursor, click, type text, send key combinations, scroll.

## Long description
Uses PowerShell Win32 API calls to control the desktop independently of any browser or application. Combine with desktop_capture to see the screen, identify coordinates, interact, and verify the result.

Available actions:
- move: move mouse cursor to (x, y)
- click: left-click, optionally at (x, y)
- double_click: double left-click, optionally at (x, y)
- right_click: right-click, optionally at (x, y)
- type: type literal text into the focused element (special characters are auto-escaped)
- key: send a raw SendKeys string for key combinations, e.g. ^c (Ctrl+C), %{F4} (Alt+F4), {ENTER}, {TAB}, {ESC}, ^a^c (Ctrl+A then Ctrl+C)
- scroll: scroll the mouse wheel at (x, y); amount positive = up, negative = down

## Input parameters
- action: which action to perform — move | click | double_click | right_click | type | key | scroll (required)
- x: X screen coordinate in pixels (required for move/scroll; optional for click/right_click/double_click)
- y: Y screen coordinate in pixels (required for move/scroll; optional for click/right_click/double_click)
- text: text to type (for action=type) or raw SendKeys string (for action=key) (required for type and key)
- amount: number of scroll notches, positive=up negative=down (optional, default: 3; for action=scroll only)

## Output
A short confirmation string describing what was done, or an error message.
