# DuckDuckGo Web Search
## Name
DuckDuckGo Web Search
## Key
web_search_ddg
## Short description
Searches the web with DuckDuckGo for the given query and returns the results page as plain text with links.
## Long description
URL-encodes the query, fetches https://duckduckgo.com/html/?q=<encoded_query>, strips HTML and returns readable text with hyperlinks preserved. DuckDuckGo's HTML-only endpoint is more resistant to bot blocking.
## Input parameters
- **query** (string, required): search query text
## Output
Plain text of the DuckDuckGo search results page with links in Markdown format [text](url).
