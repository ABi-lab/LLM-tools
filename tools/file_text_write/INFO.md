# Create or Write text to file
## Name
Write to (or create) text file
## Key
file_text_write
## Short description
Writes received text into the specified file at specified offset. Creates file if it does not exist.
## Long description
Inserts new text at specified location. Use only when creating files or writing into empty files where replace is not possible. To change existing content of files use replace_text_file instead.
## Input parameters
- **path** (string, required): path to file to write into
- **offset** (number, optional, default: 0): which line to start writing into
- **content** (string, optional, default: ''): which text to insert (can create empty files with no content)
For optimal performance limit content to max 20 lines per call. This can avoid repeating large tool calls in case of problems.
## Output
Response describes the result of the action:
  - File was successfully modified.
  - Write failed because: [reason for failure]
