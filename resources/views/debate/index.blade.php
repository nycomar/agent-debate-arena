<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Debate Arena 🎭</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%); min-height: 100vh; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        header { text-align: center; padding: 2rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        h1 { font-size: 2.5rem; background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #a55eea); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .setup-panel { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 2rem; margin: 2rem 0; border: 1px solid rgba(255,255,255,0.1); }
        .setup-panel h2 { margin-bottom: 1rem; color: #48dbfb; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #8892b0; }
        input, select, textarea { width: 100%; max-width: 400px; padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(30,30,50,0.95); color: #e0e0e0; font-size: 1rem; }
        button { padding: 0.75rem 2rem; border: none; border-radius: 10px; font-size: 1rem; cursor: pointer; transition: all 0.2s; font-weight: bold; }
        .btn-start { background: linear-gradient(90deg, #ff6b6b, #ff9f43); color: white; }
        .btn-next { background: linear-gradient(90deg, #48dbfb, #0abde3); color: #1a1a2e; }
        button:hover { transform: scale(1.05); }
        .debate-arena { display: none; margin-top: 2rem; }
        .debate-arena.active { display: block; }
        .topic-display { text-align: center; padding: 1.5rem; background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 2rem; }
        .topic-display h3 { color: #8892b0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; }
        .topic-display .topic { font-size: 1.8rem; margin-top: 0.5rem; color: #fff; }
        .agents-row { display: flex; justify-content: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .agent-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; }
        .agent-pro { background: rgba(16, 185, 129, 0.3); color: #10b981; }
        .agent-con { background: rgba(239, 68, 68, 0.3); color: #ef4444; }
        .agent-neutral { background: rgba(139, 92, 246, 0.3); color: #8b5cf6; }
        .agent-judge { background: rgba(251, 191, 36, 0.3); color: #fbbf24; }
        .agent-expert { background: rgba(59, 130, 246, 0.3); color: #3b82f6; }
        .agent-active { box-shadow: 0 0 20px currentColor; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .arguments-container { background: rgba(0,0,0,0.3); border-radius: 16px; padding: 2rem; min-height: 400px; }
        .argument { padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 12px; border-left: 4px solid; animation: slideIn 0.5s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .argument.pro { background: rgba(16, 185, 129, 0.1); border-color: #10b981; }
        .argument.con { background: rgba(239, 68, 68, 0.1); border-color: #ef4444; }
        .argument.neutral { background: rgba(139, 92, 246, 0.1); border-color: #8b5cf6; }
        .argument.judge { background: rgba(251, 191, 36, 0.1); border-color: #fbbf24; }
        .argument.expert { background: rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
        .argument-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .argument-agent { font-weight: bold; }
        .argument-role { font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 10px; }
        .argument-text { font-size: 1.1rem; line-height: 1.6; }
        .winner-banner { display: none; text-align: center; padding: 2rem; background: linear-gradient(90deg, #fbbf24, #f59e0b); border-radius: 16px; margin-top: 2rem; color: #1a1a2e; }
        .winner-banner.active { display: block; animation: pop 0.5s ease; }
        @keyframes pop { 0% { transform: scale(0); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #48dbfb; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading { text-align: center; padding: 2rem; color: #8892b0; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎭 Agent Debate Arena</h1>
            <p style="color: #8892b0; margin-top: 0.5rem;">Multi-agent AI debates in real-time</p>
        </header>
        
        <div class="setup-panel" id="setupPanel">
            <h2>Start a New Debate</h2>
            
            <div class="form-group" id="existingDebatesGroup">
                <label>Continue an existing debate:</label>
                <select id="existingDebates" style="max-width: 300px; color: #fff; background: #2a2a4a;">
                    <option value="">-- Select a debate --</option>
                </select>
                <button type="button" onclick="loadExistingDebate()" style="margin-left: 10px; padding: 0.5rem 1rem; background: #48dbfb; color: #1a1a2e; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Load →</button>
            </div>
            
            <div class="form-group">
                <label>Choose a topic:</label>
                <select id="topicSelect">
                    <option value="">-- Select a topic --</option>
                    <option value="Should AI be regulated?">Should AI be regulated?</option>
                    <option value="Is remote work better than office work?">Is remote work better than office work?</option>
                    <option value="Should social media be regulated?">Should social media be regulated?</option>
                    <option value="Is cryptocurrency the future of money?">Is cryptocurrency the future of money?</option>
                    <option value="Should college be free?">Should college be free?</option>
                </select>
            </div>
            <div class="form-group">
                <label>Or enter your own:</label>
                <input type="text" id="customTopic" placeholder="Type any topic...">
            </div>
            <div class="form-group">
                <label>Number of agents:</label>
                <select id="agentCount">
                    <option value="2">2 (Pro vs Con)</option>
                    <option value="3" selected>3 (Pro, Con, Neutral)</option>
                    <option value="4">4 (Full debate)</option>
                    <option value="5">5 (All agents)</option>
                </select>
            </div>
            <button class="btn btn-start" onclick="startDebate()">🚀 Start Debate</button>
        </div>
        
        <div class="debate-arena" id="arena">
            <div class="topic-display">
                <h3>Topic</h3>
                <div class="topic" id="topicText"></div>
            </div>
            
            <div class="agents-row" id="agentsRow"></div>
            
            <div style="text-align: center; margin-bottom: 1rem;">
                <button class="btn btn-next" id="nextBtn" onclick="nextRound()">▶️ Next Round</button>
            </div>
            
            <div class="arguments-container" id="argumentsContainer">
                <div class="loading" id="loadingMsg" style="display: none;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem;">Agents are thinking...</p>
                </div>
            </div>
            
            <div class="winner-banner" id="winnerBanner">
                <h2>🏆 Winner: <span id="winnerName"></span></h2>
                <button class="btn btn-start" onclick="location.reload()" style="margin-top: 1rem;">🔄 New Debate</button>
            </div>
        </div>
    </div>
    
    <script>
        let debateId = null;
        let debateStatus = 'pending';
        
        const agentNames = { 'pro': 'The Advocate', 'con': 'The Skeptic', 'neutral': "The Devil's Advocate", 'judge': 'The Moderator', 'expert': 'The Expert' };
        const agentClasses = { 'pro': 'agent-pro', 'con': 'agent-con', 'neutral': 'agent-neutral', 'judge': 'agent-judge', 'expert': 'agent-expert' };
        
        // Load existing debates on page load
        fetch('/api/debates')
            .then(r => r.json())
            .then(data => {
                if (data.debates && data.debates.length > 0) {
                    document.getElementById('existingDebatesGroup').style.display = 'block';
                    const select = document.getElementById('existingDebates');
                    data.debates.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.id + ': ' + d.topic + ' (' + d.status + ')';
                        select.appendChild(opt);
                    });
                }
            });
        
        function loadExistingDebate() {
            const select = document.getElementById('existingDebates');
            const selectedId = select.value;
            if (!selectedId) { alert('Please select a debate'); return; }
            
            fetch('/api/debate/' + selectedId)
                .then(r => r.json())
                .then(data => {
                    if (data.debate) {
                        debateId = data.debate.id;
                        document.getElementById('setupPanel').style.display = 'none';
                        document.getElementById('arena').classList.add('active');
                        document.getElementById('topicText').textContent = data.debate.topic;
                        showAgents(JSON.parse(data.debate.agents));
                        
                        const args = JSON.parse(data.debate.arguments || '[]');
                        const container = document.getElementById('argumentsContainer');
                        container.innerHTML = '';
                        args.forEach(arg => addArgument(arg));
                        
                        if (data.debate.status === 'finished') {
                            document.getElementById('winnerBanner').classList.add('active');
                            document.getElementById('winnerName').textContent = data.debate.winner;
                            document.getElementById('nextBtn').disabled = true;
                        }
                    }
                });
        }
        
        async function startDebate() {
            const topicSelect = document.getElementById('topicSelect').value;
            const customTopic = document.getElementById('customTopic').value;
            const topic = customTopic || topicSelect;
            if (!topic) { alert('Please select or enter a topic!'); return; }
            const agentCount = document.getElementById('agentCount').value;
            
            document.getElementById('setupPanel').style.display = 'none';
            document.getElementById('arena').classList.add('active');
            document.getElementById('topicText').textContent = topic;
            
            const response = await fetch('/api/debate/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ topic: topic, agent_count: parseInt(agentCount) })
            });
            
            const data = await response.json();
            debateId = data.debate.id;
            showAgents(data.debate.agents);
            nextRound();
        }
        
        function showAgents(agents) {
            const row = document.getElementById('agentsRow');
            row.innerHTML = agents.map(agent => 
                '<span class="agent-badge ' + agentClasses[agent.role] + '" id="agent-' + agent.role + '">' + agent.name + '</span>'
            ).join('');
        }
        
        async function nextRound() {
            const btn = document.getElementById('nextBtn');
            const loading = document.getElementById('loadingMsg');
            btn.disabled = true;
            loading.style.display = 'block';
            
            try {
                const response = await fetch('/api/debate/' + debateId + '/debate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await response.json();
                loading.style.display = 'none';
                
                if (data.argument) { addArgument(data.argument); }
                
                const agents = JSON.parse(data.debate.agents);
                const round = JSON.parse(data.debate.arguments).length;
                const agentIndex = (round - 1) % agents.length;
                
                document.querySelectorAll('.agent-badge').forEach(el => el.classList.remove('agent-active'));
                document.getElementById('agent-' + agents[agentIndex].role).classList.add('agent-active');
                
                if (data.debate.status === 'finished') {
                    document.getElementById('winnerBanner').classList.add('active');
                    document.getElementById('winnerName').textContent = data.debate.winner;
                    btn.disabled = true;
                    btn.textContent = '🏆 Debate Over';
                } else {
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                loading.style.display = 'none';
                btn.disabled = false;
            }
        }
        
        function addArgument(arg) {
            const container = document.getElementById('argumentsContainer');
            const div = document.createElement('div');
            div.className = 'argument ' + arg.role;
            div.innerHTML = '<div class="argument-header"><span class="argument-agent">' + arg.agent + '</span><span class="argument-role ' + agentClasses[arg.role] + '">' + arg.role + '</span></div><p class="argument-text">' + arg.argument + '</p>';
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
    </script>
</body>
</html>