# Tool to build a library of filesystem directories; uses caching in an SQLite database for fast access over large sets.

## crawler.php

Generates an index over the files given by the path. Triggers additional functions for more data that will complete the database.

## index.php

Shows an HTML overview of all indexed files with additional data like resolution and duration.

## dl.php

Check on a valid download request and serve file via stream.