<?php


include '../db-ayar.php';


if($_POST['action'] == 'store') {
    $views = $_POST['views'];

    $delete = $db->exec("DELETE FROM tasarla_sablon_json WHERE json_adi = '{$_POST['json_adi']}' LIMIT 1");
    $result = $db->prepare("INSERT INTO tasarla_sablon_json SET json_adi = ?, json = ?");
    $result = $result->execute(array($_POST['json_adi'],$views));
    $id = $db->lastInsertId();

    if($result) {
        header('Content-Type: application/json');
        echo json_encode($id);
    }
}else if($_POST['action'] == 'load') {
   	$cek = $db->query("SELECT * FROM tasarla_sablon_json WHERE json_adi = '{$_POST['json_adi']}' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if($cek){
        header('Content-Type: application/json');
        echo json_encode(stripslashes($cek['json']));
    }else{
        echo 0;
    }
    
}


?>