<?php

include 'init.php';

function get_view_growth($link){
  $res = mysqli_fetch_assoc(mysqli_query($link,"SELECT activity_id FROM activity WHERE activity_type='workspace' AND activity_id < (SELECT activity_id FROM activity WHERE activity_type='workspace' ORDER BY activity_time DESC LIMIT 1) ORDER BY activity_time DESC LIMIT 1"));
  $res = empty($res['activity_id']) ? 0:$res['activity_id'];
  $prev = mysqli_num_rows(mysqli_query($link, "SELECT activity_id FROM activity WHERE (activity_type='blogView' OR activity_type='postView') AND activity_id < $res"));
  $new = mysqli_num_rows(mysqli_query($link, "SELECT activity_id FROM activity WHERE (activity_type='blogView' OR activity_type='postView') AND activity_id > $res"));
  $prev = $prev == 0 ? $new:$prev;
  $growth = ($new / $prev)*100;
  return $growth;
}

function get_popularity_growth($link){
  $res = mysqli_fetch_assoc(mysqli_query($link,"SELECT activity_id FROM activity WHERE activity_type='workspace' ORDER BY activity_time DESC LIMIT 1"));
  $res = empty($res['activity_id']) ? 0:$res['activity_id'];
  $prev = mysqli_num_rows(mysqli_query($link, "SELECT activity_id FROM activity WHERE activity_id < $res"));
  $new = mysqli_num_rows(mysqli_query($link, "SELECT activity_id FROM activity WHERE activity_id > $res"));
  $prev = $prev == 0 ? $new:$prev;
  $growth = ($new / $prev)*100;
  return $growth;
}

function get_activity_data($link, $type, $scale){
  if($scale == 'day'){
    $groupby = 'DAY(activity_time)';
    $fetch = 'DAYNAME(activity_time)';
  }else if($scale == 'month'){
    $groupby = 'MONTH(activity_time)';
    $fetch = 'MONTHNAME(activity_time)';
  }else if($scale == 'year'){
    $groupby = 'YEAR(activity_time)';
    $fetch = 'YEAR(activity_time)';
  }else if($scale == 'hour'){
    $groupby = 'HOUR(activity_time)';
    $fetch = 'TIME(activity_time)';
  }
  $res = mysqli_query($link, "SELECT COUNT(activity_id) AS views,$fetch AS scale FROM activity WHERE activity_type='$type' GROUP BY $groupby");
  $data_set = array();
  $data_set['data'] = array();
  $data_set['labels'] = array();
  while($row = mysqli_fetch_assoc($res)){
    array_push($data_set['data'],(int)$row['views']);
    array_push($data_set['labels'],$row['scale']);
  }
  return $data_set;
}

function get_initial_data($link){
  $data = array();

  $data['post_count'] = mysqli_num_rows(mysqli_query($link, "SELECT post_id FROM posts"));
  $data['comment_count'] = mysqli_num_rows(mysqli_query($link, "SELECT comment_id FROM comments"));
  $data['view_growth'] = get_view_growth($link);
  $data['popularity_growth'] = get_popularity_growth($link);

  return $data;
}

function get_all_activities($link){
  $value = array(
    'data' => array(),
    'labels' => array()
  );
  $post_views = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(activity_id) AS pviews FROM activity WHERE activity_type='postView'"));
  array_push($value['data'],$post_views['pviews']);
  array_push($value['labels'], 'Posts Views');

  $post_views = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(activity_id) AS bviews FROM activity WHERE activity_type='blogView'"));
  array_push($value['data'],$post_views['bviews']);
  array_push($value['labels'], 'Blog Views');

  $post_views = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(activity_id) AS cviews FROM activity WHERE activity_type='comment'"));
  array_push($value['data'],$post_views['cviews']);
  array_push($value['labels'], 'Comments');

  return $value;
}


$possible_url = array('initialData', 'activityChart','allActivity');
$value = 'An error occurred';

if(isset($_GET['action']) && in_array($_GET['action'], $possible_url)){

  switch ($_GET['action']) {
    case 'initialData':
      $value = get_initial_data($db_conx);
      break;

    case 'activityChart':
      $type = isset($_POST['type']) ? sanitize($db_conx, $_POST['type']) : 'blogView';
      $scale = isset($_POST['scale']) ? sanitize($db_conx, $_POST['scale']) : 'date';
      $value = get_activity_data($db_conx, $type, $scale);
      break;

    case 'allActivity':
      $value = get_all_activities($db_conx);
      break;

    default:
      break;
  }

}

exit(json_encode($value));

?>
