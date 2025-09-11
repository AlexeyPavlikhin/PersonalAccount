<!DOCTYPE html>
<html>
<head>
<title>METANIT.COM</title>
<meta charset="utf-8" />
</head>
<body>
<?php

if(isset($_POST["technologies"])){
    $technologies = $_POST["technologies"];
    foreach($technologies as $item) echo "$item<br />";   
}
?>
<h3>Форма ввода данных</h3>
<form method="POST">
    <p>ASP.NET: <input type="checkbox" name="technologies[]" value="ASP.NET" onChange1='this.form.submit()'/></p>
    <p>PHP: <input type="checkbox" name="technologies[]" value="PHP" onClick='this.form.submit()'/></p>
    <p>Node.js: <input type="checkbox" name="technologies[]" value="Node.js" onClick='this.form.submit()'/></p>
    <input type="submit" value="Отправить">
</form>
</body>
</html>