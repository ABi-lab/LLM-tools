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
- path: path to file to write into (required)
- offset: which line to start writing into (optional, default: 0)
- content: which text to insert (optional, default: ''; can create empty files with no content)
## Output
Response describes the result of the action:
  - File was successfully modified.
  - Write failed because: [reason for failure]
