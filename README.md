# LLM-tools

LLM-tools is a tool server for large language models, to be integrated with https://prompt-designer.ai/ to provide tool calling abilities to configured agents.

# Quick-Start: How to run it fast

- Have PHP installed on target machine (default: the one you're using Prompt Designer at)
- Git clone repo into any folder
- Run `php index.php` from CLI
- Configure path to tool server in Prompt Designer (default: `http://localhost:7001/`)

# How it works

Observe the console. On each tool call you should be prompted with:
- What the LLM is attempting to do
- Why
- Possible responses:
  - [y]es 
  - [a]lways (session)
  - always for this [t]ool
  - [n]o
  - n[e]ver (session)
  - never for this t[o]ol
If you ever wish to modify remembered answers, they're stored in `tools.conf.json`. Modify and restart the script.

All tool calls are logged into `Tool-call-[timestamp].md` in the directory from where `php index.php` was started.

# Tool set

The tool server was initially built with a very basic tool set, which should enable LLMs to create new tools, if existing tools don't support their needs. These were:
- `file_text_read` to read existing files
- `file_text_write` to create new files
- `file_text_replace` to change existing files
- `execute` to execute CLI commands
- `web_fetch_url` to find information when needed

Packed with instructions on how to write new tools, including a new tool template, agents were instructed to build additional tools:
- `web_search_google`, which was immediately discarded, because Google doesn't like "non-human" users
- `web_search_ddg` to use DuckDuckGo search engine instead
- `web_search_brave` to use Brave search engine, because DuckDuckGo can sometimes be "difficult" too (don't be a dick now, Brave)

And with some integration to process responses on the Prompt Designer side, new tools allow:
- `desktop_capture` to enable LLMs to see the desktop, and
- `desktop_control` to enable LLMs to send mouse and keyboard actions to the system
- `image_store` allows conversion of Base64 images to actual image files hosted at specific URL (because some models prefer it that way)

Tested on Windows, I don't know about the rest, but at least on Windows, it should work (depending on how capable the LLM actually is).

And then, some tools were created just for the sake of it, to test 10b-30b model capabilities: fibonacci, dice, math_add, math_gravity. In certain cases these can actually even be usefull, considering LLMs inability to actually math.

# Full tool descriptions

Full tool descriptions are contained in their respective `tools/tool_dir/INFO.md`.

These files are used to automatically build `/catalog.json` when requested by models which support functional tool use. Consequently, adding a new tool should be as simple as creating a directory with `INFO.md` from template and `index.php` as per instructions in `INSTRUCTIONS.md` (just tell your LLM to do it).

Models, which do not support functional tool use, are given instructions on how to use tools via system prompt, using text from `INSTRUCTIONS:md`, including instructions, that the most up-to-date list of tools can be obtained with `web_fetch_url` call for `/` (default `http://localhost:7001/`), which is a "prose" version of JSON at `/catalog.json`.