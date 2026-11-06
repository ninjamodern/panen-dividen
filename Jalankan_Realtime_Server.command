#!/bin/zsh
cd "$(dirname "$0")"
echo "Menjalankan Server Real-Time BEI di http://localhost:8765 ..."
(sleep 1 && open "http://localhost:8765") &
python3 server.py

