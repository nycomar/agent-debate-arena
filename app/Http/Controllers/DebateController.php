<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class DebateController extends Controller
{
    private $spaceEndpoint = 'https://spacebase1.differ.ac';
    private $stationToken = 'rF-FhSKnudEjn23CAlL2QkDkUYeaQOZQAFxhRROo3Hc';
    private $spaceId = 'space-c6e23735-cc40-47fd-9b49-f3a9caa45ea5';
    
    public function index()
    {
        $debates = Debate::orderBy('id', 'desc')->take(20)->get();
        return view('debate.index', compact('debates'));
    }
    
    private function postToSpacebase($content, $parentId = null, $payload = []) {
        // Use Python SDK via shell
        $parentId = $parentId ?? $this->spaceId;
        $payloadJson = json_encode($payload);
        
        // Escape content for Python
        $contentEscaped = addslashes($content);
        
        $script = "
import sys, json
sys.path.insert(0, '/var/www/laravel/sdk')
from http_space_tools import HttpSpaceToolSession
from pathlib import Path

session = HttpSpaceToolSession(
    endpoint='https://spacebase1.differ.ac',
    workspace=Path('/var/www'),
    agent_name='Mastering the Debate'
)
session.connect()

intent = session.intent(
    \"$contentEscaped\",
    parent_id='$parentId',
    payload=$payloadJson
)
result = session.post(intent)
print(json.dumps(result))
";
        
        $tempFile = '/tmp/space_post_' . uniqid() . '.py';
        file_put_contents($tempFile, $script);
        
        $output = shell_exec("python3 $tempFile 2>&1");
        @unlink($tempFile);
        
        return json_decode($output, true) ?? ['error' => $output];
    }
    
    public function create(Request $request)
    {
        $topic = $request->input('topic', 'Should AI be regulated?');
        
        $personas = [
            ['name' => 'The Advocate', 'role' => 'pro', 'persona' => 'Passionate advocate making compelling arguments in favor.'],
            ['name' => 'The Skeptic', 'role' => 'con', 'persona' => 'Critical thinker challenging assumptions.'],
            ['name' => "The Devil's Advocate", 'role' => 'neutral', 'persona' => 'Taking extreme positions to test ideas.']
        ];
        
        $debate = Debate::create([
            'topic' => $topic,
            'status' => 'pending',
            'agents' => json_encode($personas),
            'arguments' => '[]'
        ]);
        
        // Post to Spacebase1
        $spaceResult = $this->postToSpacebase(
            "Debate: {$topic}",
            null,
            ['kind' => 'debate', 'debate_id' => $debate->id]
        );
        
        $spaceDebateId = $spaceResult['intentId'] ?? null;
        if ($spaceDebateId) {
            $debate->update(['space_debate_id' => $spaceDebateId]);
        }
        
        return response()->json([
            'success' => true,
            'debate' => [
                'id' => $debate->id,
                'topic' => $debate->topic,
                'status' => $debate->status,
                'agents' => json_decode($debate->agents),
                'arguments' => []
            ],
            'spacebase' => $spaceResult
        ]);
    }
    
    public function show($id)
    {
        $debate = Debate::findOrFail($id);
        return response()->json([
            'debate' => $debate,
            'arguments' => json_decode($debate->arguments ?? '[]', true)
        ]);
    }
    
    public function debate(Request $request, $id)
    {
        $debate = Debate::findOrFail($id);
        $agents = json_decode($debate->agents);
        $arguments = json_decode($debate->arguments ?? '[]', true);
        
        $round = count($arguments);
        $agentIndex = $round % count($agents);
        $agent = $agents[$agentIndex];
        
        $apiKey = env('OPENAI_API_KEY');
        
        // Default fallback
        $argumentText = "This is argument $round from {$agent->name} about {$debate->topic}.";
        
        if ($apiKey) {
            $client = new Client();
            
            $systemPrompt = match($agent->role) {
                'pro' => "You are {$agent->name}, a passionate advocate for '{$debate->topic}'. Make compelling arguments in FAVOR of this position. Be persuasive.",
                'con' => "You are {$agent->name}, a skeptical thinker. Present arguments AGAINST '{$debate->topic}'. Challenge assumptions.",
                default => "You are {$agent->name}, Devil's Advocate. Take an extreme or nuanced position on '{$debate->topic}'."
            };
            
            try {
                $response = $client->post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => "Present your argument for round " . ($round + 1) . " about: {$debate->topic}. Keep it to 2-3 sentences. Be concise but compelling."]
                        ],
                        'max_tokens' => 150,
                        'temperature' => 0.7
                    ]
                ]);
                $data = json_decode($response->getBody(), true);
                $argumentText = trim($data['choices'][0]['message']['content'] ?? $argumentText);
            } catch (\Exception $e) {
                $argumentText = "Regarding {$debate->topic}, {$agent->name} presents a compelling position.";
            }
        }
        
        $arguments[] = [
            'agent' => $agent->name,
            'role' => $agent->role,
            'argument' => $argumentText,
            'round' => $round + 1
        ];
        
        $newStatus = (count($arguments) >= count($agents) * 2) ? 'finished' : 'running';
        $winner = $newStatus === 'finished' ? 'The Skeptic' : null;
        
        $debate->update([
            'arguments' => json_encode($arguments),
            'status' => $newStatus,
            'winner' => $winner
        ]);
        
        // Post argument to Spacebase1
        $parentId = $debate->space_debate_id ?? $this->spaceId;
        $spaceResult = $this->postToSpacebase(
            "Round " . ($round + 1) . ": {$agent->name} - " . substr($argumentText, 0, 100) . "...",
            $parentId,
            ['kind' => 'argument', 'debate_id' => $debate->id, 'round' => $round + 1, 'agent' => $agent->name]
        );
        
        return response()->json([
            'success' => true,
            'debate' => [
                'id' => $debate->id,
                'topic' => $debate->topic,
                'status' => $newStatus,
                'agents' => $agents,
                'arguments' => $arguments,
                'winner' => $winner
            ],
            'argument' => end($arguments),
            'spacebase' => $spaceResult
        ]);
    }
    
    public function list()
    {
        $debates = Debate::select('id', 'topic', 'status', 'winner', 'created_at')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();
        return response()->json(['debates' => $debates]);
    }
}