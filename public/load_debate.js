function loadExistingDebate() {
    const select = document.getElementById("existingDebates");
    if (!select) {
        alert("No debates found");
        return;
    }
    const selectedId = select.value;
    if (!selectedId) {
        alert("Please select a debate first");
        return;
    }
    
    fetch("/api/debate/" + selectedId)
        .then(r => r.json())
        .then(data => {
            if (data.debate) {
                debateId = data.debate.id;
                document.getElementById("setupPanel").style.display = "none";
                document.getElementById("arena").classList.add("active");
                document.getElementById("topicText").textContent = data.debate.topic;
                showAgents(JSON.parse(data.debate.agents));
                
                const args = JSON.parse(data.debate.arguments || "[]");
                const container = document.getElementById("argumentsContainer");
                container.innerHTML = "";
                args.forEach(arg => addArgument(arg));
                
                if (data.debate.status === "finished") {
                    document.getElementById("winnerBanner").classList.add("active");
                    document.getElementById("winnerName").textContent = data.debate.winner;
                    document.getElementById("nextBtn").disabled = true;
                }
            }
        });
}
