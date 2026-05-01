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

// Get random news topic
Route::get('/news-topic', function() {
    $apiKey = env('OPENAI_API_KEY');
    
    if (!$apiKey) {
        // Fallback to news headlines
        $topics = [
            "Should AI be regulated?",
            "Is remote work better than office work?",
            "Should we ban TikTok?",
            "Is crypto the future of finance?",
            "Should college be free?",
            "Is social media bad for mental health?",
            "Should voting be mandatory?",
            "Is space exploration worth the cost?"
        ];
        return response()->json([
            'topic' => $topics[array_rand($topics)],
            'source' => 'fallback'
        ]);
    }
    
    // Use OpenAI to get a debate topic
    $client = new \GuzzleHttp\Client();
    try {
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a debate topic generator. Return exactly ONE debate topic (as a question) that would be interesting for a debate. Return ONLY the question, nothing else.'],
                    ['role' => 'user', 'content' => 'Generate a single interesting debate topic about current events or controversial issues. Keep it concise.']
                ],
                'max_tokens' => 50,
                'temperature' => 0.9
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        $topic = trim($data['choices'][0]['message']['content'] ?? 'Should we regulate AI?');
        
        return response()->json([
            'topic' => $topic,
            'source' => 'openai'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'topic' => 'Should AI be regulated?',
            'source' => 'fallback'
        ]);
    }
});

// Create debate with optional news topic
Route::post('/debate/create', function(Request $request) {
    $topic = $request->input('topic');
    $useNews = $request->input('use_news', false);
    
    // If use_news is true, get a news topic
    if ($useNews && empty($topic)) {
        $apiKey = env('OPENAI_API_KEY');
        
        if ($apiKey) {
            $client = new \GuzzleHttp\Client();
            try {
                $response = $client->post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are a debate topic generator. Return exactly ONE debate topic as a question. Keep it short and controversial. Return ONLY the question.'],
                            ['role' => 'user', 'content' => 'Generate a debate topic about current events, technology, or society. Make it something people would genuinely disagree about.']
                        ],
                        'max_tokens' => 60,
                        'temperature' => 0.9
                    ]
                ]);
                $data = json_decode($response->getBody(), true);
                $topic = trim($data['choices'][0]['message']['content'] ?? 'Should AI be regulated?');
            } catch (\Exception $e) {
                $topic = 'Should AI be regulated?';
            }
        } else {
            $topic = 'Should AI be regulated?';
        }
    }
    
    $topic = $topic ?: 'Should AI be regulated?';
    
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

// Run debate round with GPT-4
Route::post('/debate/{id}/debate', function($id) {
    $debate = Debate::findOrFail($id);
    $agents = json_decode($debate->agents);
    $arguments = json_decode($debate->arguments ?? '[]', true);
    
    $round = count($arguments);
    $agentIndex = $round % count($agents);
    $agent = $agents[$agentIndex];
    
    $apiKey = env('OPENAI_API_KEY');
    
    $argumentText = "Demo argument for round " . ($round + 1);
    
    if ($apiKey) {
        $client = new \GuzzleHttp\Client();
        
        $systemPrompt = match($agent->role) {
            'pro' => "You are {$agent->name}, a passionate advocate for {$debate->topic}. Make compelling arguments in FAVOR of this position. Be persuasive and cite benefits.",
            'con' => "You are {$agent->name}, a skeptical thinker. Present arguments AGAINST {$debate->topic}. Challenge assumptions and highlight risks.",
            default => "You are {$agent->name}, Devil's Advocate. Take an extreme or nuanced position on {$debate->topic}. Question the mainstream view."
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
            // Fallback to demo text
            $argumentText = "Regarding {$debate->topic}, {$agent->name} presents a compelling position that deserves consideration.";
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
// Get news topics for debate
Route::get('/news-topics', function() {
    try {
        $response = file_get_contents('https://news.dev13.apextech.agency/api/news/enriched-topics');
        $data = json_decode($response, true);
        $topics = $data['topics'] ?? [];
        return response()->json(['topics' => $topics]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'topics' => []]);
    }
});
