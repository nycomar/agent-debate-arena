<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebateController extends Controller
{
    // Show the debate arena UI
    public function index()
    {
        $debates = Debate::orderBy('created_at', 'desc')->take(10)->get();
        return view('debate.index', compact('debates'));
    }
    
    // Create a new debate
    public function create(Request $request)
    {
        $topic = $request->input('topic');
        $agentCount = $request->input('agent_count', 3);
        
        // Define agents with different personas
        $agents = $this->generateAgents($agentCount, $topic);
        
        $debate = Debate::create([
            'topic' => $topic,
            'status' => 'pending',
            'agents' => $agents,
            'arguments' => []
        ]);
        
        return response()->json([
            'success' => true,
            'debate' => $debate
        ]);
    }
    
    // Run one round of debate
    public function debate(Request $request, $id)
    {
        $debate = Debate::find($id);
        
        if (!$debate) {
            return response()->json(['error' => 'Debate not found'], 404);
        }
        
        $agents = json_decode($debate->agents, true);
        $arguments = json_decode($debate->arguments, true) ?? [];
        
        // Get the next agent to speak
        $round = count($arguments);
        $agentIndex = $round % count($agents);
        $agent = $agents[$agentIndex];
        
        // Generate argument - DEMO MODE (no API key needed)
        $argument = $this->generateDemoArgument($agent, $debate->topic, $round);
        
        // Store the argument
        $arguments[] = [
            'agent' => $agent['name'],
            'role' => $agent['role'],
            'argument' => $argument,
            'round' => $round + 1
        ];
        
        $debate->arguments = json_encode($arguments);
        
        // Check if debate is done (each agent spoke twice)
        if ($round + 1 >= count($agents) * 2) {
            $debate->status = 'finished';
            $debate->winner = $this->determineWinner($arguments);
        } else {
            $debate->status = 'running';
        }
        
        $debate->save();
        
        return response()->json([
            'success' => true,
            'debate' => $debate,
            'argument' => end($arguments)
        ]);
    }
    
    // Get debate status
    public function show($id)
    {
        $debate = Debate::find($id);
        
        if (!$debate) {
            return response()->json(['error' => 'Debate not found'], 404);
        }
        
        return response()->json([
            'debate' => $debate,
            'arguments' => json_decode($debate->arguments, true)
        ]);
    }
    
    // List all debates
    public function list()
    {
        $debates = Debate::orderBy('created_at', 'desc')->get();
        return response()->json(['debates' => $debates]);
    }
    
    // Generate agents with personas
    private function generateAgents($count, $topic)
    {
        $personas = [
            ['name' => 'The Advocate', 'role' => 'pro', 'persona' => 'You are a passionate advocate for the topic. Make compelling arguments in favor.'],
            ['name' => 'The Skeptic', 'role' => 'con', 'persona' => 'You are a critical thinker. Challenge assumptions and argue against the position.'],
            ['name' => 'The Devil\'s Advocate', 'role' => 'neutral', 'persona' => 'You take extreme positions to test ideas. Play devil\'s advocate on both sides.'],
            ['name' => 'The Moderator', 'role' => 'judge', 'persona' => 'You are balanced and objective. Guide the discussion fairly.'],
            ['name' => 'The Expert', 'role' => 'expert', 'persona' => 'You provide deep technical or factual insights to inform the debate.']
        ];
        
        return array_slice($personas, 0, $count);
    }
    
    // DEMO MODE: Generate fake arguments (no API needed)
    private function generateDemoArgument($agent, $topic, $round)
    {
        $arguments = [
            'pro' => [
                "Let me make the case for {$topic}. The benefits are clear and undeniable.",
                "I'd argue that {$topic} is essential for progress. Here's why...",
                "Support for {$topic} comes from solid evidence. The advantages far outweigh any drawbacks."
            ],
            'con' => [
                "While I understand the appeal, {$topic} presents serious concerns that cannot be ignored.",
                "Let me present the other side: {$topic} has significant risks we need to discuss.",
                "I respectfully disagree. {$topic} has fundamental flaws that need addressing."
            ],
            'neutral' => [
                "Both sides raise valid points. Let me challenge the assumptions on both sides.",
                "Interesting. Let me play devil's advocate here. What if we're asking the wrong question?",
                "Let me push back on the mainstream narrative about {$topic}. There are nuances here."
            ],
            'judge' => [
                "Thank you to both sides. Let me summarize the key points made so far.",
                "I appreciate the strong arguments. Let's look at the facts objectively.",
                "Based on what's been presented, I see valid concerns on multiple fronts."
            ],
            'expert' => [
                "From a technical standpoint, {$topic} involves complex considerations. Let me explain...",
                "The data shows something interesting about {$topic}. Allow me to break it down.",
                "Let me provide some context from my experience with {$topic}."
            ]
        ];
        
        $role = $agent['role'];
        $options = $arguments[$role] ?? $arguments['neutral'];
        $index = $round % count($options);
        
        return $options[$index];
    }
    
    // Determine the winner based on round count
    private function determineWinner($arguments)
    {
        $proScore = 0;
        $conScore = 0;
        
        foreach ($arguments as $arg) {
            if (in_array($arg['role'], ['pro'])) {
                $proScore++;
            } elseif (in_array($arg['role'], ['con'])) {
                $conScore++;
            }
        }
        
        if ($proScore > $conScore) {
            return 'The Advocate';
        } elseif ($conScore > $proScore) {
            return 'The Skeptic';
        }
        
        return 'Draw';
    }
}