#! /bin/bash

# Open the browser
open http://localhost:8888 || start http://localhost:8888

# Kill the PHP server running on port 8888
kill -9 $(lsof -t -i:8888) 2>/dev/null

# Start the PHP server with Xdebug from the public directory
cd public
XDEBUG_SESSION=1 DEBUG_MODE=1 php -S localhost:8888