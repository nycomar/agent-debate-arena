#!/usr/bin/env python3
"""
Spacebase1 Debate Poster - Hierarchical structure
"""

import sys
import json
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from http_space_tools import HttpSpaceToolSession

CLAIM_URL = "https://spacebase1.differ.ac/claim/space-c6e23735-cc40-47fd-9b49-f3a9caa45ea5/a0dbf6d141696538e98a4fad74fb93fcefc5"
SPACE_ID = 'space-c6e23735-cc40-47fd-9b49-f3a9caa45ea5'

def get_session():
    session = HttpSpaceToolSession(
        endpoint=CLAIM_URL,
        workspace=Path("/root/.openclaw/workspace"),
        agent_name="Mastering the Debate",
    )
    session.connect()
    return session

def create_debate(topic):
    session = get_session()
    intent = session.intent(content=f"🎭 DEBATE: '{topic}'", parent_id=SPACE_ID)
    result = session.post(intent, step="hackathon.debate.start")
    print(json.dumps({"intentId": result["intentId"], "parentId": SPACE_ID}))

def create_round(debate_intent_id, round_num):
    session = get_session()
    intent = session.intent(content=f"📝 Round {round_num}", parent_id=debate_intent_id)
    result = session.post(intent, step=f"hackathon.round.{round_num}")
    print(json.dumps({"intentId": result["intentId"], "parentId": debate_intent_id}))

def post_argument(parent_intent_id, round_num, agent, role, argument):
    session = get_session()
    emoji = {"pro": "✅", "con": "❌", "neutral": "⚖️", "judge": "🧑‍⚖️", "expert": "📚"}[role]
    text = f"{emoji} {agent} ({role}): {argument[:150]}"
    intent = session.intent(content=text, parent_id=parent_intent_id)
    result = session.post(intent, step=f"hackathon.arg.r{round_num}")
    print(json.dumps({"intentId": result["intentId"], "parentId": parent_intent_id}))

if __name__ == '__main__':
    cmd = sys.argv[1]
    if cmd == 'create':
        create_debate(' '.join(sys.argv[2:]))
    elif cmd == 'round':
        create_round(sys.argv[2], sys.argv[3])
    elif cmd == 'argument':
        post_argument(sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5], ' '.join(sys.argv[6:]))