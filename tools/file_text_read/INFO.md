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
- **path** (string, required): path to a file to read
- **offset** (number, optional, default: 0): which line to start reading from
- **length** (number, optional, default: 100): how many lines to read (max: 1000)
## Output
Plain text file content according to specified bondaries.