# Desktop Control

## Name
Desktop Control

## Key
desktop_control

## Short description
Controls the mouse and keyboard at the OS level: move cursor, click, type text, send key combinations, scroll.

## Long description
Uses PowerShell Win32 API calls to control the desktop independently of any browser or application. Combine with `desktop_capture` to see the screen, identify coordinates, interact, and verify the result.

### Available actions:
- **move**: Move mouse cursor to (x, y).
- **click**: Left-click. Optionally provide (x, y) to click a specific coordinate.
- **double_click**: Double left-click. Optionally provide (x, y).
- **right_click**: Right-click. Optionally provide (x, y).
- **type**: Types literal text. **Note:** Special characters like `+`, `^`, `%`, `~`, `(`, `)`, `[`, `]`, `{`, `}` are automatically escaped so they are typed as text, not interpreted as keys.
- **key**: Sends raw `.NET SendKeys` strings. Use this for keyboard shortcuts. **CRITICAL: Do NOT use `<enter>` or `<tab>` notation; use the curly brace notation `{ENTER}` or `{TAB}` as shown in the cheat sheet below.**
- **scroll**: Scrolls the mouse wheel at (x, y); `amount` positive = up, negative = down.

### ⚠️ Key Cheat Sheet (for action=key)
When using `action='key'`, use these standard formats:
- **Enter Key**: `{ENTER}` (Never use `<enter>`)
- **Tab Key**: `{TAB}`
- **Escape Key**: `{ESC}`
- **Backspace**: `{BACKSPACE}`
- **Control Modifiers**: `^` (e.g., `^c` for Ctrl+C, `^v` for Ctrl+V)
- **Alt Modifiers**: `%` (e.g., `%{F4}` for Alt+F4)
- **Shift Modifiers**: `+` (e.g., `+a` for Shift+A)
- **Function Keys**: `{F1}` through `{F12}`

## Input parameters
- **action** (string, required): `move` | `click` | `double_click` | `right_click` | `type` | `key` | `scroll`
- **x** (number, optional): X screen coordinate in pixels.
- **y** (number, optional): Y screen coordinate in pixels.
- **text** (string, optional): Text to type (for `type`) or raw SendKeys string (for `key`).
- **amount** (number, optional, default: 3): Number of scroll notches.

## Output
A short confirmation string describing the action or an error message.
