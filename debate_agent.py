#!/usr/bin/env python3
"""Intent-Space Native Debate Agent - responds only to topic-specific arguments"""

import sys
import json
import time
import os
from pathlib import Path

sys.path.insert(0, '/var/www/laravel/sdk')
from http_space_tools import HttpSpaceToolSession

SPACE_ID = 'space-c6e23735-cc40-47fd-9b49-f3a9caa45ea5'
OPENAI_API_KEY = os.environ.get('OPENAI_API_KEY', '')
DEBATE_TOPIC = os.environ.get('DEBATE_TOPIC', 'Should AI be regulated?')

class DebateAgent:
    def __init__(self, name, role, persona):
        self.name = name
        self.role = role
        self.persona = persona
        self.session = None
        self.last_seq = 0
        self.responded_to = set()
        
    def connect(self):
        self.session = HttpSpaceToolSession(
            endpoint='https://spacebase1.differ.ac',
            workspace=Path('/var/www'),
            agent_name=self.name
        )
        self.session.connect()
        
    def is_relevant_topic(self, payload):
        """Check if argument is about our debate topic"""
        topic = payload.get('debate_topic', '')
        content = payload.get('content', '')
        
        if topic and DEBATE_TOPIC.lower() in topic.lower():
            return True
        
        keywords = ['AI', 'artificial intelligence', 'regulation']
        content_lower = content.lower()
        if any(kw.lower() in content_lower for kw in keywords):
            return True
            
        return False
    
    def scan(self):
        """Scan for new arguments that match our topic"""
        result = self.session.scan_full(SPACE_ID)
        messages = result.get('messages', [])
        latest_seq = result.get('latestSeq', 0)
        
        new_args = []
        for m in messages:
            seq = m.get('seq', 0)
            if seq <= self.last_seq:
                continue
                
            payload = m.get('payload', {})
            content = m.get('content') or payload.get('content', '')
            
            sender = m.get('senderId', '')
            if sender == str(self.session.local_state.enrollment):
                continue
                
            intent_id = m.get('intentId')
            if intent_id in self.responded_to:
                continue
            
            if not self.is_relevant_topic(payload):
                continue
                
            new_args.append({
                'intent_id': intent_id,
                'content': content,
                'payload': payload,
                'sender': sender,
                'seq': seq
            })
        
        self.last_seq = latest_seq
        return new_args
    
    def respond_to(self, target):
        content = target['content']
        target_id = target['intent_id']
        
        if not OPENAI_API_KEY:
            reply = f"Regarding {DEBATE_TOPIC}: Interesting point."
        else:
            import requests, re
            response = requests.post(
                'https://api.openai.com/v1/chat/completions',
                headers={
                    'Authorization': f'Bearer {OPENAI_API_KEY}',
                    'Content-Type': 'application/json'
                },
                json={
                    'model': 'gpt-4o-mini',
                    'messages': [
                        {'role': 'system', 'content': self.persona},
                        {'role': 'user', 'content': f'Topic: {DEBATE_TOPIC}\n\nTheir: "{content}"\n\nJSON: {{"respond":true,"argument":"response"}}'}
                    ],
                    'max_tokens': 100,
                    'temperature': 0.7
                }
            )
            text = response.json()['choices'][0]['message']['content']
            match = re.search(r'\{[^}]+\}', text, re.DOTALL)
            if match:
                parsed = json.loads(match.group())
                if not parsed.get('respond'):
                    return None
                reply = parsed.get('argument', '').strip('"')
            else:
                reply = text.strip()
        
        intent = self.session.intent(
            str(self.name) + ": " + str(reply),
            parent_id=target_id,
            payload={
                'kind': 'counter_argument',
                'debate_topic': DEBATE_TOPIC,
                'agent': self.name,
                'role': self.role
            }
        )
        result = self.session.post(intent)
        self.responded_to.add(target_id)
        return result
    
    def run(self):
        print(f"Scanning for: {DEBATE_TOPIC}")
        new_args = self.scan()
        print(f"Found {len(new_args)} relevant arguments")
        
        for arg in new_args:
            result = self.respond_to(arg)
            if result:
                print(f"Replied: {result.get('intentId')}")
        
        return len(new_args)


if __name__ == '__main__':
    agent = DebateAgent(
        'The Skeptic',
        'con',
        'You question heavy AI regulation. Argue against it.'
    )
    agent.connect()
    count = agent.run()
    print(f"Done: {count} arguments")