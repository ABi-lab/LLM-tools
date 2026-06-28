# Read text from file
## Name
Read text from file
## Key
file_text_read
## Short description
Reads contents of text files on disk.
## Long description
Use this tool when you need to read content of text files on disk.
Tool reads only a section of a file, "length" lines from "offset" line.
This tool intentionally does not read the whole file. If file is larger than max length, use this tool multiple times.
## Input parameters
- path: path to a file to read (required)
- offset: which line to start reading from (optional, default: 0)
- length: how many lines to read (optional, default: 100, max: 1000)
## Output
Plain text file content according to specified bondaries.