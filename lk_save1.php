
<?php
    session_start();
    include('config.php');
    //echo $_SESSION['user_id'];
    echo '<p> это страница личного кабинета ', $_SESSION['user_id'], '</p>';
    echo  'Уважаемый ',  $_SESSION['user_id'], ', добро пожаловать!';
    if ($_SESSION['user_group']=="staff") {
        echo 'Вы идентифицированы как оператор';

        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT t2.* FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t GROUP by t.order_id) ORDER BY t2.order_id");
        //$query->bindParam("user_id", $user_id, PDO::PARAM_STR);
        $query->execute();
        $current_record="";
        //$result = $query->fetch(PDO::FETCH_ASSOC);

        echo "<div id='id02'>";
        echo "<form class='table' method='POST' action=''>";
        echo "<table><tr><th>Номер заказа</th><th>дата изменения</th><th>статус</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["order_id"] . "</td>";
            echo "<td>" . $row["row_creation_time"] . "</td>";
            echo "<td>" . $row["order_description"] . "</td>";
            echo "<td> <button type='submit' name='detail' value='".$row["id"]."'>Подробно</button> </td>";
            //echo "<td> <button onclick=\"document.getElementById('id01').style.display='block'\">Регистрация</button> </td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<button onclick=\"document.getElementById('id01').style.display='block'\">Регистрация</button>";

        echo "</form>";
        echo "</div>";  
        //<!-- Кнопка, открывающая модальное окно -->
        echo "<button onclick=\"document.getElementById('id01').style.display='block'\">Регистрация</button>";

        if (isset($_POST['detail'])) {
            echo $_POST['detail'];
            //document.getElementById('id01').style.display='block';


        }


    } else {
        echo 'Вы идентифицированы как клиент <BR>';
        $user_id = $_SESSION['user_id'];
        $query = $connection->prepare("SELECT t2.* FROM orders t2 WHERE t2.id in (SELECT MAX(t.id) FROM orders t WHERE t.client_id =:user_id GROUP by t.order_id) ORDER BY t2.order_id");
        $query->bindParam("user_id", $user_id, PDO::PARAM_STR);
        $query->execute();
        //$result = $query->fetch(PDO::FETCH_ASSOC);

        echo "<form class='table'>";
        echo "<table><tr><th>Номер заказа</th><th>дата изменения</th><th>статус</th></tr>";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row["order_id"] . "</td>";
            echo "<td>" . $row["row_creation_time"] . "</td>";
            echo "<td>" . $row["order_description"] . "</td>";
            echo "</tr>";
            
        }
        echo "</table>";
        echo "</form>";
    }
?>

<link rel="stylesheet" href="styles.css">
<button type='submit' name='detail' value='detail'>Подробно</button>

<!-- Модальное окно (содержит форму регистрации) -->
<div id="id01" > <!--class="modal"-->
  <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Закрыть">times;</span>
  <!--<form class="modal-content" action=#34;/action_page.php">-->
<form class="modal-content" action=#34;/action_page.php">
    <div class="container">
      <h1>Регистрация</h1>
      <p>Заполните данную форму, чтобы создать аккаунт.</p>
      <hr>
      <label for="email"><b>Электронная почта</b></label>
      <input type="text" placeholder="Введите электронную почту" name="email" required>

      <label for="psw"><b>Пароль</b></label>
      <input type="password" placeholder="Введите пароль" name="psw" required>

      <label for="psw-repeat"><b>Повторите пароль</b></label>
      <input type="password" placeholder="Повторите пароль" name="psw-repeat" required>

      <label>
        <input type="checkbox" checked="checked" name="remember" style="margin-bottom:15px"> Запомнить меня
      </label>

      <p>Создавая аккаунт вы соглашаетесь с нашими <a href="#" style="color:dodgerblue">Правилами & Условиями</a>.</p>

      <div class="clearfix">
        <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Отменить</button>
        <button type="submit" class="signup">Регистрация</button>
      </div>
    </div>
  </form>
</div>