let ws = null;
let currentGroupId = null;
let myName = "";

const setupScreen = document.getElementById("setup-screen");
const chatScreen = document.getElementById("chat-screen");
const chatterNameInput = document.getElementById("chatter-name");
const groupSizeSelect = document.getElementById("group-size");
const startBtn = document.getElementById("start-btn");
const cancelBtn = document.getElementById("cancel-btn");
const setupStatus = document.getElementById("setup-status");

const chatTitle = document.getElementById("chat-title");
const leaveBtn = document.getElementById("leave-btn");
const chatMessages = document.getElementById("chat-messages");
const messageInput = document.getElementById("message-input");
const sendBtn = document.getElementById("send-btn");
const chatStatus = document.getElementById("chat-status");

// Auto-resize textarea
messageInput.addEventListener("input", function () {
  this.style.height = "auto";
  this.style.height = Math.min(this.scrollHeight, 100) + "px";
});

function connectWebSocket() {
  // Use your WebSocket server address (localhost or tunnel)
  ws = new WebSocket("ws://localhost:8081");

  ws.onopen = () => {
    setupStatus.textContent = "✅ Connected! Looking for group...";
    setupStatus.style.color = "#a0ffa0";
    console.log("Connected to chat server");
  };

  ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    console.log("Received:", data);

    switch (data.type) {
      case "matched":
        handleMatched(data);
        break;
      case "message":
        handleMessage(data);
        break;
      case "system":
        handleSystem(data);
        break;
      case "error":
        handleError(data.message);
        break;
      default:
        console.warn("Unknown type:", data.type);
    }
  };

  ws.onclose = () => {
    console.log("Disconnected");
    handleError("Connection lost");
    resetUI();
  };

  ws.onerror = (err) => {
    console.error("WebSocket error", err);
    handleError("Connection error");
  };
}

function handleMatched(data) {
  currentGroupId = data.groupId;
  chatTitle.textContent = `👥 ${data.members.length} members`;
  setupScreen.classList.add("hidden");
  chatScreen.classList.remove("hidden");
  chatMessages.innerHTML = "";
  handleSystem({ text: `✨ Matched! Members: ${data.members.join(", ")}` });
}

function handleMessage(data) {
  const isMe = data.sender === myName;
  appendMessage(data.sender, data.text, isMe ? "me" : "user");
}

function handleSystem(data) {
  appendMessage("System", data.text, "system");
}

function handleError(msg) {
  const targetStatus = setupScreen.classList.contains("hidden")
    ? chatStatus
    : setupStatus;
  targetStatus.textContent = msg;
  setTimeout(() => {
    if (targetStatus.textContent === msg) targetStatus.textContent = "";
  }, 4000);
  if (!setupScreen.classList.contains("hidden")) alert(msg);
}

function appendMessage(sender, text, className) {
  const msgDiv = document.createElement("div");
  msgDiv.className = `message ${className}`;

  if (className !== "system") {
    const nameSpan = document.createElement("span");
    nameSpan.className = "msg-name";
    nameSpan.textContent = sender;
    msgDiv.appendChild(nameSpan);
  }

  const textSpan = document.createElement("span");
  textSpan.textContent = text;
  msgDiv.appendChild(textSpan);

  // optional timestamp
  const timeSpan = document.createElement("span");
  timeSpan.className = "msg-time";
  timeSpan.textContent = new Date().toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });
  msgDiv.appendChild(timeSpan);

  chatMessages.appendChild(msgDiv);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

function resetUI() {
  setupScreen.classList.remove("hidden");
  chatScreen.classList.add("hidden");
  currentGroupId = null;
  startBtn.disabled = false;
  cancelBtn.classList.add("hidden");
  if (ws) ws.close();
  ws = null;
}

// Event listeners
startBtn.addEventListener("click", () => {
  myName = chatterNameInput.value.trim();
  const groupSize = parseInt(groupSizeSelect.value);

  if (!myName) {
    setupStatus.textContent = "⚠️ Please enter a name";
    setupStatus.style.color = "#ff8a8a";
    return;
  }

  if (!ws || ws.readyState !== WebSocket.OPEN) {
    connectWebSocket();
  }

  const checkConn = setInterval(() => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      clearInterval(checkConn);
      ws.send(
        JSON.stringify({
          action: "find",
          groupSize: groupSize,
          chatterName: myName,
        }),
      );
      startBtn.disabled = true;
      cancelBtn.classList.remove("hidden");
      setupStatus.textContent = "🔍 Searching for group...";
      setupStatus.style.color = "#ffd966";
    }
  }, 100);
});

cancelBtn.addEventListener("click", () => {
  if (ws) ws.close();
  resetUI();
});

sendBtn.addEventListener("click", () => {
  const text = messageInput.value.trim();
  if (text && ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ action: "send", message: text }));
    messageInput.value = "";
    messageInput.style.height = "auto";
  }
});

messageInput.addEventListener("keypress", (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendBtn.click();
  }
});

leaveBtn.addEventListener("click", () => {
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ action: "leave" }));
    ws.close();
  }
  resetUI();
});
