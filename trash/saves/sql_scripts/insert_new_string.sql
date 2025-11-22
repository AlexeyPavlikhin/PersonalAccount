CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_new_string`(	IN `in_client_last_name` VARCHAR(256), 
																	IN `in_client_first_name` VARCHAR(256), 
                                                                    IN `in_client_patronymic` VARCHAR(256), 
                                                                    IN `in_email` VARCHAR(256), 
                                                                    IN `in_phone` VARCHAR(256), 
                                                                    IN `in_telegram` VARCHAR(256), 
                                                                    IN `in_client_job` VARCHAR(256), 
                                                                    IN `in_client_comment` TEXT, 
                                                                    IN `in_product_name` VARCHAR(256),
                                                                    IN `in_status` VARCHAR(256),
                                                                    IN `in_product_comment` TEXT)
BEGIN
Declare v_client_id INT; 
Declare v_tmp_int INT; 
Declare v_tmp_int2 INT; 
Declare v_tmp_text TEXT; 

SET in_client_last_name = trim(in_client_last_name);
SET in_client_first_name = trim(in_client_first_name);
SET in_client_patronymic = trim(in_client_patronymic);
SET in_email = trim(in_email);
SET in_phone = trim(in_phone);
SET in_telegram = trim(in_telegram);
SET in_client_job = trim(in_client_job);
SET in_client_comment = trim(in_client_comment);
SET in_product_name = trim(in_product_name);
SET in_status = trim(in_status);
SET in_product_comment = trim(in_product_comment);

/*создаём клиента*/
/*проверяем не создавали ли этого клиента ранее*/ 
/*пытаемся получить id клиента*/

/*Ищем по совпадению e-mail, телефона и телеграм */
SELECT COUNT(tbl.client_id)
INTO v_tmp_int
FROM (
/*	
    SELECT cl.client_id as client_id
	FROM clients cl 
	WHERE cl.client_last_name=in_client_last_name 
	AND cl.client_first_name=in_client_first_name 
	AND cl.client_patronymic=in_client_patronymic
	UNION
*/    
	SELECT e.client_id as client_id
	FROM clients_email e 
	WHERE e.email = in_email
	UNION
	SELECT p.client_id 
	FROM clients_phone p 
	WHERE p.phone = in_phone
	UNION
	SELECT t.client_id 
	FROM clients_telegram t 
	WHERE t.telegram = in_telegram
) tbl;

IF v_tmp_int = 1 THEN 
	/*Получаем id найденного клиента*/
	SELECT tbl.client_id
	INTO v_client_id
	FROM (
/*    
		SELECT cl.client_id as client_id
		FROM clients cl 
		WHERE cl.client_last_name=in_client_last_name 
		AND cl.client_first_name=in_client_first_name 
		AND cl.client_patronymic=in_client_patronymic
		UNION
*/        
		SELECT e.client_id as client_id
		FROM clients_email e 
		WHERE e.email = in_email
		UNION
		SELECT p.client_id 
		FROM clients_phone p 
		WHERE p.phone = in_phone
		UNION
		SELECT t.client_id 
		FROM clients_telegram t 
		WHERE t.telegram = in_telegram
	) tbl;    
ELSEIF v_tmp_int = 0 THEN     
	/*создаём клиента*/
    /*генерим ID клиента */
    SELECT IFNULL(MAX(client_id), 0)+1 as next_id into v_client_id from clients;
    
	INSERT INTO clients (client_id, client_last_name, client_first_name, client_patronymic, client_job, client_comment) 
	VALUES (v_client_id, in_client_last_name, in_client_first_name, in_client_patronymic, in_client_job, in_client_comment);
    
ELSE 
	/*Количество записей о клиенте больше одной. Неоьходимо схолпнуть записи */
	/*Напечатать ошибку*/
	INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Найдено несколько записей с клиентов с реквизитами: ', 'Фамилия: ', in_client_last_name, ' Имя: ', in_client_first_name, ' Отчество: ', in_client_patronymic, ' Почта: ', in_email, ' Телефон: ', in_phone, ' Телеграм: ', in_telegram));
    
    /*Начинаем схлопывание клиента*/
	CALL united_client(in_email, in_phone, in_telegram, v_client_id);

    INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Проведена дедубликация. Текущую запись объединили с записью ID: <', v_client_id, ">"));
    
END IF;


/*Обновляем запись клиета, дописываем все остальные атрибуты*/
CALL concat_clients_property (v_client_id, 
							  in_client_last_name,
                              in_client_first_name,
                              in_client_patronymic,
                              in_client_job,
                              in_client_comment);                            
                             

/*записываем e-mail*/
IF in_email <> "" THEN
	SELECT COUNT(*)
	INTO v_tmp_int
	FROM clients_email e WHERE e.email = in_email;

	IF v_tmp_int > 0 THEN
		/*проверяем, что этот email принадлежит нашему клиненту*/
		SELECT COUNT(*)
		INTO v_tmp_int2
		FROM clients_email e 
		WHERE e.email = in_email
		AND e.client_id = v_client_id;

		IF v_tmp_int2 = 0 THEN
			/* напечатать ошибку*/
			INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Email <', in_email, '> не принадлежит клиенту с ID <', v_client_id, '>'));
		END IF;

	ELSE
		/* создаём запись с email*/
		INSERT INTO clients_email (client_id, email) 
		VALUES (v_client_id, in_email);
	END IF;
END IF;

/*записываем phone*/
IF in_phone <> "" THEN
	SELECT COUNT(*)
	INTO v_tmp_int
	FROM clients_phone e WHERE e.phone = in_phone;

	IF v_tmp_int > 0 THEN
		/*проверяем, что этот номер телефона принадлежит нашему клиненту*/
		SELECT COUNT(*)
		INTO v_tmp_int2
		FROM clients_phone e 
		WHERE e.phone = in_phone
		AND e.client_id = v_client_id;
				
		IF v_tmp_int2 = 0 THEN
			/* напечатать ошибку*/
			INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Phone <', in_phone, '> не принадлежит клиенту с ID <', v_client_id, '>'));
		END IF;
		
	ELSE
		/* создаём запись с номером телефона*/
		INSERT INTO clients_phone (client_id, phone) 
		VALUES (v_client_id, in_phone);
	END IF;
END IF;

/*записываем telegram*/
IF in_telegram <> "" THEN
	SELECT COUNT(*)
	INTO v_tmp_int
	FROM clients_telegram e WHERE e.telegram = in_telegram;

	IF v_tmp_int > 0 THEN
		/*проверяем, что этот id telegram принадлежит нашему клиненту*/
		SELECT COUNT(*)
		INTO v_tmp_int2
		FROM clients_telegram e 
		WHERE e.telegram = in_telegram
		AND e.client_id = v_client_id;
				
		IF v_tmp_int2 = 0 THEN
			/* напечатать ошибку*/
			INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Telegram <', in_telegram, '> не принадлежит клиенту с ID <', v_client_id, '>'));
		END IF;
		
	ELSE
		/* создаём запись с номером телефона*/
		INSERT INTO clients_telegram (client_id, telegram) 
		VALUES (v_client_id, in_telegram);
	END IF;
END IF;

/*записываем product*/
IF in_product_name <> "" THEN
	/*Опеределяем сколько зписей такого продукта уже есть в справочнике*/
	SELECT COUNT(*)
	INTO v_tmp_int
	FROM products t WHERE t.product_name = in_product_name;

	IF v_tmp_int = 0 THEN
		/* создаём запись с продуктом*/
        INSERT INTO products (product_name) 
		VALUES (in_product_name);
	END IF;

	/*Проверяем, нет ли уже такого продукта у клиента*/
    SELECT COUNT(*)
	INTO v_tmp_int
	FROM sales t 
	WHERE t.client_id = v_client_id
	AND t.product_id = (SELECT product_id FROM products t WHERE t.product_name = in_product_name);

	IF v_tmp_int = 0 THEN
		/*Проверяем справочник статусов. Если он пустой, то наполняем начальной записью*/
		SELECT COUNT(*) INTO v_tmp_int2 FROM sales_status ss  WHERE ss.status_id = 1;
        
        IF v_tmp_int2 = 0 THEN
			INSERT INTO sales_status (status_id, status_name) VALUES (1, "НЕ ОПРЕДЕЛЁН");
        END IF;
		
		/*Делаем запись о продуктах*/
		INSERT INTO sales
		(`client_id`,
		`product_id`,
		`sale_date`,
		`product_comment`,
        `sale_status_id`)
		VALUES
		(
		v_client_id,
		(SELECT MAX(product_id) FROM products t WHERE t.product_name = in_product_name),
		'1977-02-04',
		in_product_comment,
        1
		);
	ELSE
		/*обновляем комметарий и статус по продукту у клиента*/
        IF in_product_comment <> "" THEN
			/*текущее значение комментрия*/
			SELECT s.product_comment 
            INTO v_tmp_text 
            FROM sales s 
            WHERE s.client_id = v_client_id 
            AND product_id = (SELECT MAX(product_id) 
							  FROM products t 
                              WHERE t.product_name = in_product_name);
			
			/*Записиываем новое значение комментария*/
            UPDATE sales 
			SET product_comment = TRIM(CONCAT(v_tmp_text, ". ", in_product_comment))
			WHERE client_id = v_client_id 
			AND product_id = (SELECT MAX(product_id) FROM products t WHERE t.product_name = in_product_name);
		END IF;
	END IF;
    
    /*Работаем со статусом продуктa*/
    IF in_status <> "" THEN
		/*Проверяем есть ли такой статус в справочнике? */
		SELECT COUNT(*)
		INTO v_tmp_int
		FROM sales_status ss WHERE ss.status_name = in_status;

		IF v_tmp_int = 0 THEN
			/* создаём запись справочник статусов*/
			INSERT INTO sales_status (status_name) 
			VALUES (in_status);
		END IF;    
		
		/*обновляем статус в таблице sales*/
		UPDATE sales
		SET sale_status_id = (SELECT MAX(ss.status_id) FROM sales_status ss WHERE ss.status_name = in_status)
		WHERE client_id = v_client_id 
		AND product_id = (SELECT MAX(product_id) FROM products t WHERE t.product_name = in_product_name);
	END IF;
END IF;

END