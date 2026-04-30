#!/usr/bin/env python3
"""
Spacebase1 Autonomous Poster - Integrated with PHP
Receives JSON from stdin, posts to Spacebase1
"""

import sys
import json
from pathlib import Path

sys.path.insert(0, '/var/www/laravel/sdk')

from http_space_tools import HttpSpaceToolSession

CLAIM_URL = "https://spacebase1.differ.ac/claim/space-c6e23735-cc40-47fd-9b49-f3a9caa45ea5/a0dbf6d141696538e98a4fad74fb93fcefc5"
SPACE_ID = 'space-c6e23735-cc40-47fd-9b49-f3a9caa45ea5'

# Cache session
_session = None

def get_session():
    global _session
    if _session is None:
        _session = HttpSpaceToolSession(
            endpoint=CLAIM_URL,
            workspace=Path("/var/www"),
            agent_name="Mastering the Debate",
        )
        _session.connect()
    return _session

def post_intent(content, parent_id):
    session = get_session()
    intent = session.intent(content=content, parent_id=parent_id)
    result = session.post(intent, step="hackathon.auto")
    return result["intentId"]

if __name__ == '__main__':
    # Read JSON from stdin (passed by PHP)
    try:
        data = json.load(sys.stdin)
    except:
        print(json.dumps({"error": "Invalid JSON"}))
        sys.exit(1)
    
    action = data.get("action")
    
    if action == "create_debate":
        topic = data.get("topic", "Untitled")
        intent_id = post_intent(f"🎭 DEBATE: '{topic}'", SPACE_ID)
        print(json.dumps({"intentId": intent_id, "parentId": SPACE_ID}))
    
    elif action == "create_round":
        parent_id = data.get("parentId")
        round_num = data.get("round", 1)
        intent_id = post_intent(f"📝 Round {round_num}", parent_id)
        print(json.dumps({"intentId": intent_id, "parentId": parent_id}))
    
    elif action == "post_argument":
        parent_id = data.get("parentId")
        round_num = data.get("round", 1)
        agent = data.get("agent", "Unknown")
        role = data.get("role", "neutral")
        argument = data.get("argument", "")[:150]
        
        emoji = {"pro": "✅", "con": "❌", "neutral": "⚖️", "judge": "🧑‍⚖️", "expert": "📚"}[role]
        content = f"{emoji} {agent}: {argument}"
        
        intent_id = post_intent(content, parent_id)
        print(json.dumps({"intentId": intent_id, "parentId": parent_id}))
    
    else:
        print(json.dumps({"error": "Unknown action"}))