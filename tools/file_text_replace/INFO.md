# Replace text in file
## Name
Replace text in file
## Key
file_text_replace
## Short description
Replaces existing file content with new content.
## Long description
Use this when changing contents of files.
To add a line between two lines, search for original (both lines) and replace with new text (content added between the lines).
If original search returns multiple results, widen the section by one line until search returns only one result; search for two lines before and after inserted text and replace with these four lines and new content inserted between them.
If expected searched content is not found, this means that file has been changed and may require re-reading it.
Concrete example of inserting "inserted text" between "text_line2" and "text_line3":
Content of file:
```
text_line1
text_line2
text_line3
text_line4
...
text_line8
text_line2
text_line3
text_line9
```
Search for:
```
text_line2
text_line3
```
Replace with:
```
text_line2
inserted text
text_line3
```
Because search returns multiple results we don't know which is the correct line to insert at. Therefore we widen the search by one line and retry:
Search for:
```
text_line8
text_line2
text_line3
text_line9
```
Replace with:
```
text_line8
text_line2
inserted text
text_line3
text_line9
```
This search will correctly identify only 1 position where the content is to be replaced.
## Input parameters
- path: path to a file to read (required)
- search: which text to replace (required)
- replace: new text content to replace the matched text with (optional, default: ''; no replace value removes the search text)
## Output
Response describes the result of the action:
  - File was successfully modified.
  - Write failed because: [reason for failure]
