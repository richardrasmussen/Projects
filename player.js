class Player {
  name;
  id;
  g_id;
  match_id;
  gametype;
  p_num;
  start_score;
  score;
  match_avg;
  best_score = 0;
  turns = [];
  throws = [];
  match_turns = [];
  match_throws = [];
  wins;
  total_wins;
  total_games;
  high_avg = 0;
  high_checkout;
  avg;
  doubles_attempted = 0;
  checkout_type;
  attempted_double = false;



  constructor(id, name, gametype, match_id) {
    this.id = id;
      this.name = name;
      this.gametype = gametype;
      this.match_id = match_id;
      if (gametype === '501') {
        this.score = 501;
        this.start_score = 501;
      } else if (gametype === '301') {
        this.score = 301;
        this.start_score = 301;
      }
      else {
        this.score = 501;
        this.start_score = 501;
      }
      this.get_stats();
     
  }

  set_checkout(c)
  {
    this.checkout_type = c;
  }

  set_gid(g) {
    this.g_id = g;
  }

  set_score(s) {
    this.score = s;
  }

  set_p_num(p) {
    this.p_num = p;
  }
  
  get_wins(match_id)
  {
    let return2 = 0;
    $.ajax({
      async: false,
      url: "get_match_wins.php?p_id="+this.id+"&match_id="+match_id,
      success: function(result){
        console.log("result: ", result);
        return2 = result;
      }});
      return return2;
  }

  get_score() {
    return this.score;
  }

  get_name() {
    return this.name;
  }

  get_id() {
    return this.id;
  }

  get_turn_count() {
    return this.turns.length;
  }

  get_best_score() {
    return this.best_score;
  }

  get_doubles() {
    return this.doubles_attempted;
  }

  get_match_avg() {
    let mavg = 0;
    $.ajax({
      async: false,
      url: "get_match_avg.php?p_id="+this.id+"&match_id="+this.match_id+"&game_id="+this.g_id,
      success: function(result){
        console.log("result avg: ", result);
        mavg = result;

      }});
      return mavg;

  }

  get_game_avg() {
    var x = 0;
    var count = 0;
    var avg = 0;
    this.turns.forEach((element) => {
      element.forEach((t) => {
        x += parseInt(t);
        
      });
      count += 1;
    });

    if (count == 0) {
      avg = 0;
    } else {
      avg = x / count;
    }

    console.log("count: ", count);
    console.log("x: ", x);
    console.log("AVG: ", avg);
    return avg.toFixed(2);
  }

  throw_count() {
    return this.throws.length;
  }

  undo() {
    if (this.throws.length > 0) {
      var count = this.throws.length;
      var x = this.throws.pop();
      this.score += x[0];
      $(".current-throws .t"+count).html("");
      console.log("1", this.throws);
    } else {
      
      var temp = this.turns.pop();
      temp.forEach((element) => {
        this.throws.push(element[0]);
      });
      console.log("2", this.throws);
      var x = this.throws.pop();
      this.score += x[0];
    }

      let xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        console.log("response: ", this.responseText);
      }
    
    xhttp.open("GET", "undo_turn.php?&g_id="+this.g_id, true);
    xhttp.send();
    }
  }

  get_turns() {
    var x = "";
    this.turns.forEach((element) => {
      var y = 0;
      element.forEach((t) => {
        if (y == 0) {
          x += t[1];
        } else {
          x += "-" + t[1];
        }
        y += 1;
      });
      x += " | ";
    });
    return x;
  }

  get_last_turn() {
    if (this.turns.length == 0) {
      return "";
    }
    else {
    var out = "";
    var last = [];
    let sum = 0;
    last = this.turns[this.turns.length - 1];
    var y = 0;
    last.forEach((element) => {
      if (y == 0) {
        out += element[1];
        sum += parseInt(element[0]);
      } else {
        out += "-" + element[1];
        sum += parseInt(element[0]);
      }
      y += 1;
    });
    out += " | " + sum;
    return out;
  }
}

  get_throws() {
    var x = "";
    var count = 1;
    this.throws.forEach((element) => {
      console.log("element: ", element);
      x = element[0];
      $(".current-throws .t"+count).html(x);
      count++;
    });
    //return x;
  }

  throw(val, target) {
    console.log("Attempted: ", this.attempted_double);
    console.log("Attempt count: ", this.doubles_attempted);
    console.log("Checkout: ", this.checkout_type);
    if (this.score == 50 || (this.score <= 40 && this.score % 2 == 0)) {
      this.attempted_double = true;
    }
    


    if (this.score - val < 0) {
      for (let i = 0; i < this.throws.length; i++) {
        this.score += this.throws[i][0];
      }

      this.throws.push([0, "bust"]);
      this.turns.push(this.throws);
      this.send_turn(this.throws);
      this.get_throws();
      this.throws = [];
      return "done";
    } 
    else if (this.score - val == 0) {
      console.log("Target: ", target);
      if (this.checkout_type == "Double")
      {
        if (target[0] == "D") {
          this.score = this.score - val;
          this.throws.push([val, target]);
          this.turns.push(this.throws);
          this.send_turn(this.throws);
          return "winner";
        }
        else {
          for (let i = 0; i < this.throws.length; i++) {
            this.score += this.throws[i][0];
          }
          this.throws.push([0, "bust"]);
          this.turns.push(this.throws);
          this.send_turn(this.throws);
          this.get_throws();
          this.throws = [];
          return "done";
        }
      }
      else if (this.checkout_type == "Three-out") {
        if (target[0] == "D") {
          this.score = this.score - val;
          this.throws.push([val, target]);
          this.turns.push(this.throws);
          this.send_turn(this.throws);
          return "winner";
        }
        else if (this.doubles_attempted >= 3) 
        {
          this.score = this.score - val;
          this.throws.push([val, target]);
          this.turns.push(this.throws);
          this.send_turn(this.throws);
          return "winner";
        }
        else {
          for (let i = 0; i < this.throws.length; i++) {
            this.score += this.throws[i][0];
          }
          this.throws.push([0, "bust"]);
          this.turns.push(this.throws);
          this.send_turn(this.throws);
          this.get_throws();
          this.throws = [];
          return "done";
        }
      }
      this.score = this.score - val;
      this.throws.push([val, target]);
      this.turns.push(this.throws);
      this.send_turn(this.throws);
      return "winner";
    }
    else {
      this.score = this.score - val;
      this.throws.push([val, target]);
      console.log("throws: ", this.throws[0]);
      if (this.throws.length == 3 ) {
        this.send_turn(this.throws);
        this.turns.push(this.throws);
        this.get_throws();
        this.throws = [];
        if (this.get_score() != 0) {
          return "done";
        }
        else {
          return "winner";
        }

      } else {
        return "again";
      }
    }
    
  }


  send_turn(turn) {
    var sendstring = "";
    var sendval = "";
    let val = 0;
    var turncount = this.turns.length + 1;

    

    if (this.attempted_double == true) {
      this.doubles_attempted += 1;
    }
    
    console.log("turn: ", turn);
    
    turn.forEach((e) => {
      sendval += e[0] + ",";
      sendstring += e[1] + ",";
      val += parseInt(e[0]);
    });
    if (val > this.best_score) {
      this.best_score = val;
    }
    this.match_turns.push(turn);
    let xhttp1 = new XMLHttpRequest();
    xhttp1.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        console.log("response: ", this.responseText);
  
        console.log("sent: ", this.id);
      }
    };
    
    xhttp1.open("GET", "send_turn.php?p_id="+this.id+"&g_id="+this.g_id+"&turn=" + sendstring + "&turnval=" + sendval + "&turncount=" + turncount, true);
    xhttp1.send();
  }

  send_avg() {
    var g_avg = this.get_game_avg();
    console.log("g_avg: ", g_avg);
    var player_num = this.p_num;
    var gmid = this.g_id;
    var pid = this.id;
    let xhttp2 = new XMLHttpRequest();
    xhttp2.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        console.log("response: ", this.responseText);
  
      }
    };
    
    xhttp2.open("GET", "send_avg.php?g_id="+gmid+"&game_avg="+g_avg+"&p_num="+player_num+"&p_id="+pid, true);
    xhttp2.send();
  }

get_alltime_avg() {
  let return1 = 0;
  $.ajax({
    async: false,
    url: "get_player_avg.php?p_id="+this.id,
    success: function(result){
      console.log("result: ", result);
      return1 = result;
    }});
    return return1;
 }
  

  p_new_game(game_id) {
    this.set_match_turns();
    this.g_id = game_id;
    this.throws = [];
    this.turns = [];    
    this.score = this.start_score;
    this.best_score = 0;

  
  }

  get_stats() {
    let return1 = [];
  $.ajax({
    async: false,
    url: "get_stats.php?playerid="+this.id,
    success: function(result){
      console.log("result: ", result);
      return1 = result.split(",");
      
      
    }});
    this.total_games = parseInt(return1[0]);
      this.wins = parseInt(return1[1]);
      this.avg = parseFloat(return1[2]);
      this.high_avg = return1[3];
      this.high_checkout = return1[4];
      console.log(return1[2]);
 }


}



