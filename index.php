<?php
  $set = '#f1c40f';
  $good = '#3498db';
  $bad = '#e74c3c';
  $enemy = '#7f8c8d';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    ul {
      list-style: none;
      border: solid 1px #f5f5f5;
      width: 90vh;
      height: 90vh;
      margin: 5vh auto;
    }
    ul li {
      width: calc(100% / 6);
      height: calc(100% / 6);
      line-height: calc(100% / 6);
      text-align: center;
      float: left;
      border: solid 1px #f5f5f5;
    }
    ul li.set {
      background: <?= $set ?>;
    }
    ul li.good {
      background: <?= $good ?>;
    }
    ul li.bad {
      background:  <?= $bad ?>;;
    }
    ul li.enemy {
      background: <?= $enemy ?>;;
    }
    ul li.selected {
      box-shadow: inset 0 0 0 5px #34495e;
    }
    ul li.next {
      box-shadow: inset 0 0 0 5px #2c3e50;
    }
    div {
      opacity: 0.5;
      position: fixed;
      left: 0;
      bottom: 0;
    }
    div #good {
      color: #00f;
    }
    div #bad {
      color: #f00;
    }
  </style>
</head>
<body>
  <ul>
    <?php for ($i = 0; $i <= 35; $i++) { ?>
      <li></li>
    <?php } ?>
  </ul>
  <div>
    Good:<span id="good"></span><br>
    Bad:<span id="bad"></span>
  </div>
  <script type="text/javascript">
  const ws = new WebSocket('ws://192.168.86.49:3000');
  let places = {};
  let ready = {};
  let order = null;
  let player = parseInt(window.prompt("Playerを入力してください", ""));
  if (player != 1) player = 2;
  let lis = [];
  let selected = null;

  function start(_ready) {
    if (_ready) {
      setColor(true);
      return
    }

    function isGood(i) {
      return i % 2 == 0;
    }
    function whichPayer(i) {
      return i < 8 ? 1 : 2;
    }
    function isMe(i) {
      return whichPayer(i) == player;
    }
    function canSet(i) {
      if (player == 1) {
        if (i < 24) return false;
      } else {
        if (i > 12) return false;
      }
      if (i % 6 == 0) return false;
      if (i % 6 == 5) return false;

      return true;
    }
    function existResident(i) {
      if (places[i]) {
        return isMe(places[i]);
      }
      return false;
    }
    function setColor(_ready) {
      document.querySelectorAll('ul li').forEach((li) => {
        let cur = lis.indexOf(li);
        if (ready[1] && ready[2]) li.classList.remove('set');
        li.classList.remove('good');
        li.classList.remove('bad');
        li.classList.remove('enemy');
        li.classList.remove('selected');
        li.classList.remove('next');

        if (selected !== null) {
          // current
          if (selected == cur) {
            li.classList.add('selected');
          }

          let top = selected - 6;
          if (top == cur && !existResident(top)) {
            li.classList.add('next');
          }

          let left = selected - 1;
          if (left == cur && !existResident(left)) {
            li.classList.add('next');
          }

          let right = selected + 1;
          if (right == cur && !existResident(right)) {
            li.classList.add('next');
          }

          let bottom = selected + 6;
          if (bottom == cur && !existResident(bottom)) {
            li.classList.add('next');
          }
        }

        let ghost = places[cur];
        if (ghost === null) return

        if (isMe(ghost)) {
          li.classList.add(isGood(ghost) ? 'good' : 'bad');
        } else {
          if (ready[1] && ready[2]) li.classList.add('enemy');
        }
      });
      let myGood = 0;
      let myBad = 0;
      let enemyGood = 0;
      let enemyBad = 0;
      Object.values(places).forEach((row) => {
        if (row == null) return;
        if (isMe(row)) {

        } else {
          if (isGood(row)) {
            enemyGood += 1;
          } else {
            enemyBad += 1;
          }
        }
      })

      document.querySelector('#good').innerText = '';
      for (let i = 0; i < 4 - enemyGood; i++) {
        document.querySelector('#good').innerText += '●';
      }
      document.querySelector('#bad').innerText = '';
      for (let i = 0; i < 4 - enemyBad; i++) {
        document.querySelector('#bad').innerText += '●';
      }
      if (_ready) return;

      ws.send(JSON.stringify({
        ready: ready,
        places: places,
        order: order,
      }));
    }

    let _configs = [0, 2, 4, 6];

    Object.values(places).forEach((val) => {
      if (val === null) return;
      delete _configs[val - (player == 1 ? 0 : 8)]
    })
    let configs = [];
    _configs.map((config) => {
      configs.push(config)
    })
    document.querySelectorAll('ul li').forEach((li) => {
      lis.push(li);
      let cur = lis.indexOf(li);
      if (places[cur] === undefined) places[cur] = null;
      if (canSet(cur)) li.classList.add('set');

      li.onclick = ((e) => {
        let cur = lis.indexOf(e.target);

        // set good ghost.
        if (configs.length != 0 && !ready[player]) {
          if (places[cur] !== null) return;
          if (!canSet(cur)) return;

          e.target.classList.add('good');

          let config = configs.shift();
          let add = player == 1 ? 0 : 8;
          places[cur] = config + add;
          setColor();
          if (configs.length != 0) return;

          let j = 1;
          for (let i in places) {
            if (places[i] !== null) continue;
            if (!canSet(i)) continue;
            places[i] = j + add;
            j += 2;
          }
          ready[player] = true;
          setColor();

        } else {
          if (!ready[1] || !ready[2]) return;
          if (player != order) return;

          if (selected) {
            // Cancel
            if (cur == selected) {
              selected = null;
            } else {
              if (existResident(cur)) return; // 既にいる
              if (!document.querySelector(`ul li:nth-child(${cur + 1})`).classList.contains('next')) return;
              places[cur] = places[selected];
              places[selected] = null;
              selected = null;
              order = order == 1 ? 2 : 1;
            }

          } else {
            if (places[cur] === null) return;
            selected = cur;
          }
          setColor();
        }
      });
    })
    setColor();
  }
  function conn() {
    let _ready = false;
    ws.onopen = ((e) => {
      ws.send('');
      setInterval(function() {
        start(true)
      }, 1000)
    })

    // メッセージの待ち受けイベント
    ws.onmessage = e => {
      try {
        let data = JSON.parse(e.data);
        places = data.places || {};
        ready = data.ready || {1: false, 2: false};
        order = data.order || 1;

        if (!_ready) start();
        _ready = true;
      } catch(e) {
      }
    }

    // エラー発生時のイベント
    ws.onerror = e => {
      console.log('接続に失敗:${e.data}')
      console.log(e)
    }
  }
  conn();
  </script>
</body>
</html>
