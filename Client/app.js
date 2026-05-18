let ws = null;
let currentGroupId = null;
let myName = '';

const setupScreen = document.getElementById('setup-screen');
const chatScreen = document.getElementById('chat-screen');
const chatterNameInput = document.getElementById('chatter-name');
const groupSizeSelect = document.getElementById('group-size');
const startBtn = document.getElementById('start-btn');
const cancelBtn = document.getElementById('cancel-btn');
const setupStatus = document.getElementById('setup-status');

const chatTitle = document.getElementById('chat-title');
const leaveBtn = document.getElementById('leave-btn');
const chatMessages = document.getElementById('chat-messages');
const messageInput = document.getElementById('message-input');
const sendBtn = document.getElementById('send-btn');
const chatStatus = document.getElementById('chat-status');

function connectWebSocket() {
    // Using Cloudflare Tunnel for public access
    ws = new WebSocket('wss://there-partnership-seeking-compare.trycloudflare.com');

    ws.onopen = () => {
        setupStatus.textContent = 'Connected to server!';
        setupStatus.style.color = 'green';
        console.log('Connected');
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        console.log('Received:', data);

        switch (data.type) {
            case 'matched':
                handleMatched(data);
                break;
            case 'message':
                handleMessage(data);
                break;
            case 'system':
                handleSystem(data);
                break;
            case 'error':
                handleError(data.message);
                break;
            default:
                console.warn('Unknown message type:', data.type);
        }
    };

    ws.onclose = () => {
        console.log('Disconnected');
        handleError('Disconnected from server');
        resetUI();
    };

    ws.onerror = (err) => {
        console.error('WebSocket error:', err);
        handleError('WebSocket error occurred');
    };
}

function handleMatched(data) {
    currentGroupId = data.groupId;
    chatTitle.textContent = 'Group Chat';
    setupScreen.classList.add('hidden');
    chatScreen.classList.remove('hidden');
    
    chatMessages.innerHTML = ''; // Clear previous
    handleSystem({ text: `Matched! Members: ${data.members.join(', ')}` });
}

function handleMessage(data) {
    const isMe = data.sender === myName;
    appendMessage(data.sender, data.text, isMe ? 'me' : 'user');
}

function handleSystem(data) {
    appendMessage('System', data.text, 'system');
}

function handleError(msg) {
    if (setupScreen.classList.contains('hidden')) {
        chatStatus.textContent = msg;
    } else {
        setupStatus.textContent = msg;
    }
    alert(msg);
}

function appendMessage(sender, text, className) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `message ${className}`;
    
    if (className !== 'system') {
        const senderDiv = document.createElement('div');
        senderDiv.style.fontSize = '10px';
        senderDiv.style.fontWeight = 'bold';
        senderDiv.textContent = sender;
        msgDiv.appendChild(senderDiv);
    }

    const textDiv = document.createElement('div');
    textDiv.textContent = text;
    msgDiv.appendChild(textDiv);

    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function resetUI() {
    setupScreen.classList.remove('hidden');
    chatScreen.classList.add('hidden');
    currentGroupId = null;
    startBtn.disabled = false;
    cancelBtn.classList.add('hidden');
}

// Event Listeners
startBtn.addEventListener('click', () => {
    myName = chatterNameInput.value.trim();
    const groupSize = parseInt(groupSizeSelect.value);

    if (!myName) {
        setupStatus.textContent = 'Please enter your name';
        setupStatus.style.color = 'red';
        return;
    }

    if (!ws || ws.readyState !== WebSocket.OPEN) {
        connectWebSocket();
    }

    // Wait for connection before sending
    const checkConn = setInterval(() => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            clearInterval(checkConn);
            ws.send(JSON.stringify({
                action: 'find',
                groupSize: groupSize,
                chatterName: myName
            }));
            startBtn.disabled = true;
            cancelBtn.classList.remove('hidden');
            setupStatus.textContent = 'Searching for a group...';
            setupStatus.style.color = 'blue';
        }
    }, 100);
});

cancelBtn.addEventListener('click', () => {
    if (ws) {
        ws.close();
    }
    resetUI();
});

sendBtn.addEventListener('click', () => {
    const text = messageInput.value.trim();
    if (text && ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({
            action: 'send',
            message: text
        }));
        messageInput.value = '';
    }
});

messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendBtn.click();
    }
});

leaveBtn.addEventListener('click', () => {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({
            action: 'leave'
        }));
    }
    if (ws) {
        ws.close();
    }
    resetUI();
});
