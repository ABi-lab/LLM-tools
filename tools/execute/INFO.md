# Execute
Execute a shell command.
## Name
Execute command
## Key
execute
## Short description
Executes a shell / command prompt / power shell command and returns the response.
## Long description
Executes a shell / command prompt / power shell command and returns the response.
For example, command "dir" in working directory "c:\some_directory" returns a list of files or whatever a dir command executed in c:\some_directory would return.
For example, command "node script.mjs" in working directory "c:\some_directory" runs "node script.mjs" in "c:\some_directory" and returns the response.
## Input parameters
- command: command text (required)
- working_directory: path to the working directory for the command (required)
## Output
- what the execution of that command returned
