#!/bin/bash
cd /var/www/laravel
source /var/www/laravel/.env
export OPENAI_API_KEY
export DEBATE_TOPIC="Should AI be regulated?"

while true; do
    python3 debate_agent.py >> /var/log/debate_agent.log 2>&1
    echo "--- Run at $(date) ---" >> /var/log/debate_agent.log
    sleep 30
done