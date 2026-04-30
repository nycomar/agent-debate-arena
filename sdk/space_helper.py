#!/usr/bin/env python3
"""
Spacebase1 Helper for Hackathon
Usage:
    python space_helper.py post "Your message here"
    python space_helper.py scan
    python space_helper.py watch
"""

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from http_space_tools import HttpSpaceToolSession
import json
import os

# Config
CLAIM_URL = os.environ.get('CLAIM_URL', 'https://spacebase1.differ.ac/claim/space-479c5f45-9c19-4bfa-a667-5b6b7c62cc5f/2625fcc1897fdd5f3afb800fbbe9c2349c1a')
CLAIM_TOKEN = os.environ.get('CLAIM_TOKEN', '2625fcc1897fdd5f3afb800fbbe9c2349c1a')
SPACE_ID = 'space-479c5f45-9c19-4bfa-a667-5b6b7c62cc5f'

def get_session():
    session = HttpSpaceToolSession(
        endpoint=CLAIM_URL,
        workspace=Path("/root/.openclaw/workspace"),
        agent_name="MasterDebatingAgent",
    )
    try:
        session.signup(CLAIM_URL)
    except:
        pass  # Already claimed
    session.connect()
    return session

def post_intent(content, parent_id=None):
    session = get_session()
    if parent_id is None:
        parent_id = SPACE_ID
    
    intent = session.intent(
        content=content,
        parent_id=parent_id,
    )
    
    result = session.post_and_confirm(
        intent,
        step="hackathon.post",
        confirm_space_id=parent_id
    )
    return result

def scan_intents():
    session = get_session()
    return session.scan(SPACE_ID)

def watch_space():
    session = get_session()
    while True:
        intents = session.scan(SPACE_ID)
        print(f"\n=== {len(intents)} intents in space ===")
        for i in intents[:10]:
            print(f"- {i.get('intentId', 'unknown')}: {i.get('content', '')[:60]}...")
        import time
        time.sleep(5)

if __name__ == '__main__':
    cmd = sys.argv[1] if len(sys.argv) > 1 else 'help'
    
    if cmd == 'post':
        content = ' '.join(sys.argv[2:])
        result = post_intent(content)
        print(f"✅ Posted: {result['intentId']}")
    elif cmd == 'scan':
        intents = scan_intents()
        for i in intents:
            print(f"{i.get('intentId')}: {i.get('content')}")
    elif cmd == 'watch':
        watch_space()
    else:
        print(__doc__)