var none_key = 'adsi78g87ylhsfdhADSF*((Ysdf';
var recipient = document.getElementById('to').innerText;
var from = document.getElementById('from').innerText;
var inputBox = document.getElementById('msg-input');
var msgBox = document.getElementById('msg-box');
var hNum = document.getElementById('h-num'); 
var username = document.getElementById('username').innerText;
var menu = document.getElementById('menu');

msgBox.scroll(0, msgBox.scrollHeight);

const ws = connectWebSocket("ws://localhost:8080?username=" + username + "&user_id=" + from);

inputBox.addEventListener('keypress', function(event) {
    if (event.key === 'Enter' && recipient !== none_key) {
        event.preventDefault();
        sendMsg(inputBox, recipient, from);
    }
});

function sendMsg(inputBox, recipient, me) {
    let payload = {
        msg: inputBox.value,
        to: recipient,
        from: me
    };
    ws.send(JSON.stringify(payload));
    msgBox.innerHTML += `<div class="chat chat-end">
                            <div class="chat-bubble">${inputBox.value}</div>
                        </div>`;
    inputBox.value = '';
    msgBox.scroll(0, msgBox.scrollHeight);
}

function connectWebSocket(sUrl) {
    const socket = new WebSocket(sUrl);

    socket.onopen = function(event) {
        console.log('Connection With The Server Opened.');
    };

    socket.onmessage = function(event) {
        try {
            let msg = JSON.parse(event.data);
    
            if (msg['new_user'] !== undefined) {
                let userList = menu.querySelectorAll('li');
                let userExists = Array.from(userList).some(element => {
                    const link = element.querySelector('a');
                    if (link) {
                        const url = new URL(link.href);
                        const userIdFromHref = url.searchParams.get('to');
                        return userIdFromHref === msg['user_id'];
                    }
                    return false;
                });
    
                if (!userExists) {
                    const newItem = document.createElement('li');
                    const newLink = document.createElement('a');
                    newLink.href = `index.php?to=${msg['user_id']}`;
                    newLink.textContent = msg['new_user'];
                    newItem.appendChild(newLink);
                    menu.appendChild(newItem);
                }
    
            } else {
                if (msg['from'] === recipient) {
                    const chatBubble = `<div class="chat chat-start">
                                            <div class="chat-bubble">${msg['msg']}</div>
                                        </div>`;
    
                    if (hNum.innerText === '1') {
                        msgBox.innerHTML += chatBubble;
                    } else {
                        msgBox.innerHTML = chatBubble;
                        hNum.innerText = '1';
                    }
                }
            }
    
            msgBox.scrollTop = msgBox.scrollHeight;
    
        } catch (e) {
            console.error("Error parsing message:", e);
        }
    };    

    socket.onclose = function(event) {
        console.log('Connection With The Server Closed.');
    };

    socket.onerror = function(event) {
        if (event.wasClean) {
            console.log(`Connection closed cleanly, code=${event.code}, reason=${event.reason}`);
        } else {
            console.error('Connection closed abruptly.');
        }
        console.log('Connection With The Server Closed.');
    };

    return socket;
}