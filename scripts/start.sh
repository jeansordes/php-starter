#! /bin/bash

# Ouvrir le navigateur
open http://localhost:8888

# Kill le serveur PHP qui tourne sur le port 8888
kill -9 $(lsof -t -i:8888)

# Lancer le serveur PHP avec Xdebug
XDEBUG_SESSION=1 DEBUG_MODE=1 php -S localhost:8888