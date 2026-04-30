<?php

use Illuminate\Support\Facades\Route;
use App\Models\Debate;
use Illuminate\Http\Request;

// List debates
Route::get('/debates', function() {
    return response()->json([
        'debates' => \DB::table('debates')
            ->select('id', 'topic', 'status', 'winner', 'created_at')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
    ]);
});

// Create debate
Route::post('/debate/create', function(Request $request) {
    $topic = $request->input('topic');
    $agentCount = $request->input('agent_count', 3);
    
    $personas = [
        ['name' => 'The Advocate', 'role' => 'pro'],
        ['name' => 'The Skeptic', 'role' => 'con'],
        ['name' => "The Devil's Advocate", 'role' => 'neutral'],
        ['name' => 'The Moderator', 'role' => 'judge'],
        ['name' => 'The Expert', 'role' => 'expert']
    ];
    $agents = array_slice($personas, 0, $agentCount);
    
    $debate = Debate::create([
        'topic' => $topic,
        'status' => 'pending',
        'agents' => json_encode($agents),
        'arguments' => '[]'
    ]);
    
    return response()->json([
        'success' => true,
        'debate' => [
            'id' => $debate->id,
            'topic' => $debate->topic,
            'status' => $debate->status,
            'agents' => json_decode($debate->agents),
            'arguments' => []
        ]
    ]);
});

// Get single debate
Route::get('/debate/{id}', function($id) {
    $debate = Debate::findOrFail($id);
    return response()->json([
        'debate' => $debate,
        'arguments' => json_decode($debate->arguments ?? '[]', true)
    ]);
});

// Run debate round - simplified for now (no OpenAI/Spacebase1)
Route::post('/debate/{id}/debate', function($id) {
    $debate = Debate::findOrFail($id);
    $agents = json_decode($debate->agents);
    $arguments = json_decode($debate->arguments ?? '[]', true);
    
    $round = count($arguments);
    $agentIndex = $round % count($agents);
    $agent = $agents[$agentIndex];
    
    // Demo argument (no API key for now)
    $argumentText = "This is argument $round from {$agent['name']} about {$debate->topic}.";
    
    $arguments[] = [
        'agent' => $agent['name'],
        'role' => $agent['role'],
        'argument' => $argumentText,
        'round' => $round + 1
    ];
    
    $newStatus = (count($arguments) >= count($agents) * 2) ? 'finished' : 'running';
    $winner = $newStatus === 'finished' ? 'The Advocate' : null;
    
    $debate->update([
        'arguments' => json_encode($arguments),
        'status' => $newStatus,
        'winner' => $winner
    ]);
    
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
        'argument' => end($arguments)
    ]);
});