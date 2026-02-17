<?php
//$a0 = '{"Name":"Алексей","Name_2":"\u041f\u0430\u0432u043bu0438u0445u0438u043d","Name_3":"AlexeyPavlikhin","Email":"pavlikhin@gmail.com","Phone":"+7 (903) 101-89-37","Checkbox":"yes","payment":{"sys":"tinkoff","systranid":"7659818103","orderid":"1448373555","products":[{"name":"u0414u043eu0441u0442u0443u043f u043a u0442u0435u0441u0442u0443 u043du0430 1 u0447u0435u043b.","quantity":1,"amount":5,"price":"5"}],"amount":"5"},"formid":"form644050497","formname":"Cart","API-key":"D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT"}';
$url_str = "Name=%D0%90%D0%BB%D0%B5%D0%BA%D1%81%D0%B5%D0%B9&Name_2=%D0%9F%D0%B0%D0%B2%D0%BB%D0%B8%D1%85%D0%B8%D0%BD&Name_3=AlexeyPavlihin&Email=pavlikhin%40gmail.com&Phone=%2B7+%28903%29+101-89-37&Textarea=%D0%9D%D0%B5%D1%82+%D0%B2%D0%BE%D0%BF%D1%80%D0%BE%D1%81%D0%BE%D0%B2&Checkbox=yes&payment%5Bsys%5D=tinkoff&payment%5Bsystranid%5D=7695032769&payment%5Borderid%5D=1992217563&payment%5Bproducts%5D%5B0%5D%5Bname%5D=%D0%94%D0%BE%D1%81%D1%82%D1%83%D0%BF+%D0%BA+%D1%82%D0%B5%D1%81%D1%82%D1%83+%D0%BD%D0%B0+1+%D1%87%D0%B5%D0%BB.&payment%5Bproducts%5D%5B0%5D%5Bquantity%5D=1&payment%5Bproducts%5D%5B0%5D%5Bamount%5D=5&payment%5Bproducts%5D%5B0%5D%5Bprice%5D=5&payment%5Bamount%5D=5&formid=form644050497&formname=Cart&API-key=D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT";
parse_str($url_str, $v_obj);


$json = json_encode($v_obj, JSON_UNESCAPED_UNICODE);
echo $json;
echo "<br/>=============<br/>";
echo "API-key=".$v_obj["API-key"]."<br/>";
echo "Name=".$v_obj["Name"]."<br/>";
echo "Name_2=".$v_obj["Name_2"]."<br/>";
echo "Name_3=".$v_obj["Name_3"]."<br/>";
echo "Email=".$v_obj["Email"]."<br/>";
echo "Phone=".$v_obj["Phone"]."<br/>";
echo "PRODUCT_NAME=".$v_obj["payment"]["products"][0]["name"]."<br/>";





//echo json_encode(json_decode($a0), JSON_UNESCAPED_UNICODE);
//
//$a0 = html_entity_decode(preg_replace("/U\+([0-9A-F]{4})/", "&#x\\1;", $a0), ENT_NOQUOTES, 'UTF-8');
//$a1 = "\u0410\u043b";
//$a1 = json_encode($a0);
//u0410u043bu0435u043au0441u0435u0439;
//$a0 = unicode_decode($a0);
//$a1
//$a1 = mb_convert_encoding($a0, 'UTF-8', 'UTF-8');
//echo($a1);
//print_r($a1);
//echo mb_detect_encoding($a1, ['UTF-8'], true);
//echo mb_convert_encoding($a1, 'Windows-1251', "UTF-8, ISO-8859-1, Windows-1252");


/*
$jsonString = '{"text":"\u0410ФФФ"}';
$data = json_decode($jsonString);
echo $data->text;
*/

//echo mb_convert_encoding('\\u0410', 'UTF-8', 'UTF-8');

//echo iconv('Windows-1251', 'UTF-8', $a0);

?>

