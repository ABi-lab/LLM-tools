# Brave Web Search
## Name
Brave Web Search
## Key
web_search_brave
## Short description
Searches the web with Brave Search for the given query and returns the results page as plain text with links.
## Long description
URL-encodes the query, fetches https://search.brave.com/search?q=<encoded_query>, strips HTML and returns readable text with hyperlinks preserved. Brave Search is privacy-focused and bot-friendly.
## Input parameters
- query: search query text (required)
## Output
Plain text of the Brave search results page with links in Markdown format [text](url).
