var none_key = 'adsi78g87ylhsfdhADSF*((Ysdf';
var recipient = document.getElementById('to').innerText;
var from = document.getElementById('from').innerText;
var inputBox = document.getElementById('msg-input');
var msgBox = document.getElementById('msg-box');
var hNum = document.getElementById('h-num'); 

msgBox.scroll(0, msgBox.scrollHeight);

const ws = connectWebSocket("ws://localhost:8080?user_id=" + from);

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
            if (msg['from'] == recipient) {
                if (hNum.innerText == '1') {
                    msgBox.innerHTML += `<div class="chat chat-start">
                                            <div class="chat-bubble">${msg['msg']}</div>
                                        </div>`;
                } else {
                    msgBox.innerHTML = `<div class="chat chat-start">
                                            <div class="chat-bubble">${msg['msg']}</div>
                                        </div>`;
                    hNum.innerText = '1'; 
                }
            }
            msgBox.scroll(0, msgBox.scrollHeight);
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