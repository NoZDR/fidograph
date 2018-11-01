<?php if (isset($_GET['area'])) { $area = htmlentities($_GET['area']); } else { $area = ""; } ?>
<?php if (isset($_GET['arrows'])) { $arrows = true; } else { $arrows = false; } ?> 
<!doctype html>
<html>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<head>
  <title><?php if (($area != "")&&($area != "0")) {echo "Пути хождения сообщений в ".$area;} else {echo "FidoGraph v0.1 by NoZDR";} ?></title>
  <style>
   body { background-color: #000; color: white; font-family: Consolas, Arial; margin:0; padding:0;}
   #area {position: absolute; left:10px; top:10px; z-index:1; font-size:200%;}
   #title {position: absolute; right:10px;}
  </style>
  <!-- для рисования графа используем библиотеку vis.js (http://visjs.org) -->
  <script type="text/javascript" src="vis-network.min.js"></script>
  <script type="text/javascript" src="jquery-3.3.1.min.js"></script>
  <link href="vis-network.min.css" rel="stylesheet" type="text/css"/>
</head>
<body>
  <form action="/fidograph/" method="get">
    <select id="area" name="area">
      <option value=0>Выберите эху:</option>
<?php
  $base = "c:/node2613/fido/base/"; // путь до сквишовых баз
  $my_node = "5020/2613"; // узел, с которого на это всё смотрим, и которого нет в путях
  // эти эхи не рисовать
  $exclude = array("dupearea","carbonarea","nozdr.fileecho","nozdr.forwards","nozdr.local_tmp","nozdr.fileecho","nozdr.official","nozdr.test","nozdr.robots","r50.sysop_tmp");

  // функция вырезания номера сети из комбинации "сеть/узел"
  function get_net($node) { $m = explode("/",$node); if (count($m)>1) { return $m[0]; } else { return -1; } }

  // вычитываем, какие есть эхи
  $files = array();
  $entries = scandir($base,1);
  foreach ($entries as $entry)
  {
    $fn = pathinfo($entry, PATHINFO_FILENAME);
    $ext = pathinfo($entry, PATHINFO_EXTENSION);
    if (($ext=="sqd") and !in_array($fn,$exclude)) { $files[] = $fn; }
  }
  asort($files);

  // и засовываем их в выпадающий список
  foreach ($files as $file)
  {
    if ($area == $file) { $selected = " selected"; } else { $selected = ""; }
    echo "      <option value=\"".$file."\"".$selected.">".$file."</option>\n";
  }
?>
    </select>
  </form>
<?php
if (($area != "") && ($area != "0") && !in_array($area,$exclude))
{

  // полное имя файла
  $fn = $base.$area.".sqd";
  if (file_exists($fn))
  {

?>
  <div align="right" id="title"><h3>Пути хождения сообщений в <?php echo $area; ?></h3></div>
  <div id="paths"></div>
<?php

  // вычитываем весь файл в строку
  $str = nl2br(file_get_contents($fn));

  $all_nodes = Array();
  $all_pairs = Array();

  // ищем строку PATH:
  preg_match_all('/PATH: (.*?)<br \/>/',$str,$paths);

  // в цикле бежим по строкам PATH
  for ($i=0; $i< count($paths[0]); $i++) 
  {
    $pairs = Array();
    $path = $paths[1][$i];
    
    // разбиваем PATH на узлы
    $nodes = explode(" ", $path);
    // если последний узел в пути не наш, добавляем в путь наш
    if ($nodes[count($nodes)-1] <> $my_node) { $nodes[]=$my_node; }

    $j = 0;
    // перебираем узлы
    while ($j < count($nodes)-1)
    {
      // первый узел в паре
      $first = $nodes[$j];
      // второй узел в паре
      $second = $nodes[$j+1];
      // если у второго узла в паре нет сети, то копируем сеть из первого и апдейтим второй
      if (get_net($second)==-1)
      {
        $second = get_net($first).'/'.$second;
        $nodes[$j+1] = $second;
      }
      // составляем новую пару        
      $pairs[] = $first.' '.$second;
      $j++;
    }
    // закончили разбор очередного пути, аккумулируем всё в общих массивах узлов и пар
    foreach($nodes as $node) { $all_nodes[]=$node; }
    foreach($pairs as $pair) { $all_pairs[]=$pair; }
  }

  //на всякий случай добавим в список узлов наш узел, чтобы для пустой базы был хотя бы один
  $all_nodes[] = $my_node;

  // оставялем только уникальные значения и сортируем
  $all_nodes = array_unique($all_nodes); asort($all_nodes);
  if (count($all_pairs)>0) { $all_pairs = array_unique($all_pairs); asort($all_pairs); }

?>
<script type="text/javascript">
  var nodes = [
<?php
  // вставляем узлы в яваскриптовый массив
  foreach ($all_nodes as $node)
  {
     $net = get_net($node);
     echo "{'id':'$node','label':'$node','group':'";
     if ($node==$my_node) { echo "diamond"; } else { echo $net;}
     echo "'},\n";
  }
?>
  ];
  var edges = [
<?php
  // вставляем связи
  foreach ($all_pairs as $pair)
  {
     $links = explode(" ",$pair);
     echo "{from:'$links[0]', to:'$links[1]'";
     $arrows = true; //а пусть стрелочки будут всегда
     if ($arrows) { echo ",  arrows:'to'"; }
     echo "},\n";
  }
?>
  ];

  // подгоняем канвас под размер экрана
  $("#paths").css('height',$(window).height());
  $("#paths").css('width',$(window).width());

  // рисуем
  var container = document.getElementById('paths');
  var data = {nodes:nodes, edges:edges};
  var options = {
     nodes:{shape:'dot',size:20,font:{size:15,color:'#ffffff'},borderWidth:2},
     edges:{width:3},
     groups:{diamond:{color:{background:'red',border:'white'},shape:'diamond'}}
  };
  var network = new vis.Network(container,data,options);

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
<?php
  }
}
?>
<script type="text/javascript">
$(document).ready(function()
{ 
  $('#area').change(function() { this.form.submit(); }); 
});
</script>
</body>
</html>
