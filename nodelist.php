<?php

  $default_net = "5020";
  $default_nodelist = "Z2DAILY.305";

  if (isset($_GET['net'])) { $my_net = htmlentities($_GET['net']); } else { $my_net = $default_net; } 
  if (isset($_GET['nodelist'])) { $nodelist = htmlentities($_GET['nodelist']); } else { $nodelist = $default_nodelist; }
  if (!file_exists($nodelist)) { $nodelist = $default_nodelist; }

  $net = "";

  $host = "";
  $host_line = "";
  $host_links = 0;

  $hub = "";
  $hub_line = "";
  $hub_links = 0;

  $host_info = "";

  $nodes = Array();

  $f = fopen($nodelist, 'r');
  // если получилось открыть файл
  if ($f) 
  {
    // читаем его построчно, пока не дойдём до конца
    while (($line = fgets($f)) !== false) 
    {
      // если не комментарий
      if ($line[0] != ';' and $line[0] != '')
      {
        // разбиваем строку на токены и анализируем первый и второй токены
        $tokens = explode(",", $line);
        $t0 = $tokens[0];
        $t1 = $tokens[1];

        // если хост нашей сети, то заполняем сеть
        if ($t0 == "Region")
        {
            // сбрасываем сеть
            $net = ""; 
        }

        // если хост нашей сети, то заполняем сеть
        if ($t0 == "Host") 
        {
          // мы попали в начало нашей сети
          if ($t1 == $my_net) 
          {
            $host = 0;
            $net=$my_net;
            $host_line = $line;
            $host_links = 0;

            $host_info = "$tokens[2], $tokens[3]";
          } 
          // начинается новая сеть
          else
          {
            // если перед этим была наша сеть
            if ($net == $my_net)
            {
              // добавляем инфу о последнем хабе
              if ($hub!="") { $nodes[] = Array($hub, $host, $hub_line, $hub_links, $hub); }
            }
            // сбрасываем сеть
            $net = ""; 
          }
        }

        // если находимся внутри сети
        if ($net == $my_net)
        {
          // если хаб
          if ($t0 == "Hub")
          {
            //если это не первый хаб, то записываем его
            if ($hub != "")
            {
              // добавляем его к списку узлов
              $nodes[] = Array($hub, $host, $hub_line, $hub_links, $hub);
            }
            // увеличиваем счётчик хабов
            $host_links++;
            // запоминаем текущего хаба
            $hub = $t1;
            // и его строчку
            $hub_line = $line;
            // обнуляем счётчик узлов подхабника
            $hub_links = 0;
          }
          //остальные - узлы
          elseif ($t0 != "Host" && $t0 != "Region")
          {
            // запоминаем текущий узел
            $node = $t1;
            // если нода в подхабнике
            if ($hub!="")
            {
              // увеличиваем счётчик узлов подхабника
              $hub_links++;
              // добавляем узел к списку узлов
              $nodes[] = Array($node, $hub, $line, 0, $hub);
            }
            // если нода зацелена к хосту
            else
            {
              // увеличиваем счётчик узлов хоста
              $host_links++;
              // добавляем узел к списку узлов
              $nodes[] = Array($node, $host, $line, 0, $host);
            }
          }
        }
      }
    }

    // а теперь запишем инфу о хосте
    if ($host !== "") { $nodes[] = Array(0, 0, $host_line, $host_links, 0); }

    // и закроем нодлист
    fclose($f);

  } 
  else 
  {
    echo 'Невозможно открыть указанный файл';
  }

  $rou_nodes = array();
  $rou_present = false;

  $rou_file = "n$my_net.rou";
  if (file_exists($rou_file))
  {
    $rou_present = true;
    $f = fopen($rou_file, 'r');
    // если получилось открыть файл
    if ($f) 
    {
      // читаем его построчно, пока не дойдём до конца
      while (($line = fgets($f)) !== false) 
      {
        $line = preg_replace('/\s+/', ' ', $line);
        if ($line[0] != ";" and strlen($line) > 1)
        {
          $line = substr($line,5);

          $pairs = array();
          $rou_nodes = explode(" ", $line);

          $j = 0;
          // перебираем узлы
          while ($j < count($rou_nodes)-2)
          {
            // первый узел в паре
            $first = $rou_nodes[$j+1];
            // второй узел в паре
            $second = $rou_nodes[0];
            // составляем новую пару        
            $pairs[] = array($first, $second);
            $j++;
          }
          // запоминаем получившиеся пары
          foreach($pairs as $pair) { $rou_pairs[] = $pair; }
        }
      }
      fclose($f);
    }
  }

  $tru_nodes = array();
  $tru_present = false;

  $tru_file = "n$my_net.tru";
  if (file_exists($tru_file))
  {
    $tru_present = true;
    $f = fopen($tru_file, 'r');
    // если получилось открыть файл
    if ($f) 
    {
      // читаем его построчно, пока не дойдём до конца
      while (($line = fgets($f)) !== false) 
      {
        $line = preg_replace('/\s+/', ' ', $line);
        if ($line[0] != ";" and strlen($line) > 1)
        {
          $line = substr($line,5);
          $line = str_replace(';','',$line);
          $pairs = array();
          $tru_nodes = explode(" ", $line);

          $j = 0;
          // перебираем узлы
          while ($j < count($tru_nodes)-2)
          {
            // первый узел в паре
            $first = $tru_nodes[$j+1];
            // второй узел в паре
            $second = $tru_nodes[0];
            // составляем новую пару        
            $pairs[] = array($first, $second);
            $j++;
          }
          // запоминаем получившиеся пары
          foreach($pairs as $pair) { $tru_pairs[] = $pair; }
        }
      }
      fclose($f);
    }
  }


?>

<!doctype html>
<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf8">
<head>
  <title>Нодлист сети N<?php echo "$my_net ($nodelist)";?></title>
  <style>
   body { background-color: #000; color: white; font-family: Consolas, Arial; margin:0; padding:0;}
   #area {position: absolute; left:10px; top:10px; z-index:1; font-size:200%;}
   #title {position: absolute; right:10px;}
   #info {position: absolute; left:10px;}
  </style>
  <!-- для рисования графа используем библиотеку vis.js (http://visjs.org) -->
  <script type="text/javascript" src="vis-network.min.js"></script>
  <script type="text/javascript" src="jquery-3.3.1.min.js"></script>
  <link href="vis-network.min.css" rel="stylesheet" type="text/css"/>
</head>
<body>

  <div align="right" id="title"><h3><?php
if ($host === "") 
{ 
  echo "такой сети нет"; 
} 
else 
{ 
  echo "$host_info (N$my_net)<br>$nodelist"; 
  if ($rou_present) { echo "<br>$rou_file"; }
  if ($tru_present) { echo "<br>$tru_file"; }
}
?></h3></div>
  <div align="left"  id="info"></div>
  <div id="paths"></div>

<script type="text/javascript">
  var nodes = 
  [
<?php
  // вставляем узлы
  foreach ($nodes as $node) 
  { 
    $title = substr(trim($node[2]),0,150);
    echo "{id:$node[0], label:'$node[0]', group:$node[4], title:\"$title\", value:$node[3]},\n"; 
  }
?>
  ];
  var edges = 
  [
<?php
  // вставляем связи
  foreach ($nodes as $node) { if ($node[0] != 0) { echo "{from:$node[0], to:$node[1]},\n"; } }
  if ($rou_present) { foreach ($rou_pairs as $pair) { echo "{from:$pair[0], to:$pair[1]},\n"; } }
  if ($tru_present) { foreach ($tru_pairs as $pair) { echo "{from:$pair[0], to:$pair[1], dashes:true },\n"; } }
?>
  ];

  // подгоняем канвас под размер экрана
  $("#paths").css('height',$(window).height());
  $("#paths").css('width',$(window).width());
  
  // рисуем
  var container = document.getElementById('paths');
  var data = {nodes:nodes, edges:edges};
  var options = {
     nodes:{shape:'dot',size:5,font:{size:15,color:'#ffffff'},borderWidth:2},
     edges:{width:1},
     groups:{diamond:{color:{background:'red',border:'white'},shape:'diamond'}}
  };
  var network = new vis.Network(container,data,options);
//  network.on('click', alert(1));

$(document).ready(function()
{ 
  $(window).bind('resize', function(e)
  {
    if (window.RT) clearTimeout(window.RT);
    window.RT = setTimeout(function()
    {
      this.location.reload(false); /* false to get page from cache */
    }, 100);
  });
});

</script>
<script type="text/javascript">
//$(document).ready(function()
//{ 
//  $('#area').change(function() { this.form.submit(); }); 
//});
</script>
</body>
</html>