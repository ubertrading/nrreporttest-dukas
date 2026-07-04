<?php

include 'config.php';

$dttoday = strtotime("now");
$dtfrom = "";
$dtto = "";
if (date('l') == "Monday") {
  $dtfrom = date("Y-m-d", strtotime("today", $dttoday));
  $dtto = date("Y-m-d", strtotime("next sunday", $dttoday));
} else if (date('l') == "Sunday") {
  $dtfrom = date("Y-m-d", strtotime("last monday", $dttoday));
  $dtto = date("Y-m-d", strtotime("today", $dttoday));
} else {
  $dtfrom = date("Y-m-d", strtotime("last monday", $dttoday));
  $dtto = date("Y-m-d", strtotime("next sunday", $dttoday));
}

if (isset($_POST["action"])) {

  $response = array();
  if ($_POST["action"] === "getCalendar") {
    $datefrom = $_POST["datefrom"];
    $dateto = $_POST["dateto"];
    $newsId = $_POST["newsId"];
    $news = $_POST["news"];
    $site = $_POST["site"];
    $exact_time = isset($_POST["exact_time"]) ? $_POST["exact_time"] : null;

    if (!isset($site) || empty($site)) {
      $site = "NY";
    }
    $tbl_prefix = strtolower($site) . "_";
    $servername = $db_servername;
    $username = $db_username;
    $password = $db_password;
    $database = $db_database;
    $table = $tbl_prefix . $db_table;

    $datefrom_t = 0;
    if (!isset($datefrom) || empty($datefrom)) {
      $a = explode('-', $dtfrom);
      $datefrom_t = mktime(0, 0, 0, $a[1], $a[2], $a[0]) * 1000;
    } else {
      $a = explode('-', $datefrom);
      $datefrom_t = mktime(0, 0, 0, $a[1], $a[2], $a[0]) * 1000;
    }

    $dateto_t = 0;
    if (!isset($dateto) || empty($dateto)) {
      $a = explode('-', $dtto);
      $dateto_t = mktime(23, 59, 59, $a[1], $a[2], $a[0]) * 1000;
    } else {
      $a = explode('-', $dateto);
      $dateto_t = mktime(23, 59, 59, $a[1], $a[2], $a[0]) * 1000;
    }

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
      $response["status"] = "error";
      $response["data"] = "Error connecting to database.";
    } else {
      $response["status"] = "success";
      $response["data"] = array();

      $sql = "SELECT 
	      MIN(`timestamp`) as timestamp, 
	      `news_id`, 
	      `news`, 
	      `event_time`, 
	      `value`, 
	      `forecast`, 
	      `forecast_avg`, 
	      `prior`, 
	      `source`
	      FROM 
	     `{$table}`";

      $sql .= " WHERE ";

      $sql .= "(event_time >= {$datefrom_t} AND event_time <= {$dateto_t})";
      if (isset($exact_time) && !empty($exact_time)) {
        $sql .= " AND event_time = {$exact_time} ";
      }
      if (isset($newsId) && !empty($newsId)) {
        $sql .= " AND (";
        $a = explode(",", $newsId);
        $ostr = ' ';
        foreach ($a as $id) {
          $sql .= $ostr . " `news_id` = {$id} ";
          $ostr = ' OR ';
        }
        $sql .= ") ";
      }
      if (isset($news) && !empty($news)) {
        $sql .= " AND (";
        $a = explode(",", $news);
        $ostr = ' ';
        foreach ($a as $id) {
          $sql .= $ostr . " `news` COLLATE UTF8_GENERAL_CI like '%{$id}%'";
          $ostr = ' OR ';
        }
        $sql .= ") ";
      }

      $sql .= " GROUP BY `event_time`, `news_id`, `news`, `value`, `forecast`, `forecast_avg`, `prior`, `source`";
      $sql .= " ORDER by `event_time`, `news_id` ASC, MIN(`timestamp`) = 0 ASC, MIN(`timestamp`) ASC ";
      //$sql .= " ORDER by `event_time`,`news_id ASC`,`timestamp ASC`";
      // $_sql = "SELECT


      # error_log($sql);
      $res = $conn->query($sql);

      if ($res !== NULL && $res !== false) {
        while ($row = $res->fetch_assoc()) {
          // var_dump($row);
          $response['data'][] = $row;
        }
      } else {
        error_log($conn->error);
        error_log($sql);
      }
      $conn->close();
    }
  } else {
    $response["status"] = "error";
    $response["data"] = "unsupported query.";
  }
  echo json_encode($response);
}

?>
