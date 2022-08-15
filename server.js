const WebSocketServer = require('ws').Server;
const wss = new WebSocketServer({host: '0.0.0.0', port: 3000});

let memory = '{}';
let clients = [];
wss.on('connection', ws_client => {
  clients.push(ws_client);
  ws_client.send(memory);
  ws_client.on('message', data => {
    if (data.toString()) memory = data.toString();
    let _memory = JSON.parse(memory)
    console.log(JSON.stringify(_memory));
    clients.forEach((client) => {
      client.send(JSON.stringify(_memory));
    })
  });
});
