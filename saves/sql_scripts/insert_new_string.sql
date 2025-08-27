CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_new_string`(	IN `in_client_second_name` VARCHAR(256), 
																	IN `in_client_first_name` VARCHAR(256), 
                                                                    IN `in_client_patronymic` VARCHAR(256), 
                                                                    IN `in_email` VARCHAR(256), 
                                                                    IN `in_phone` VARCHAR(256), 
                                                                    IN `in_telegram` VARCHAR(256), 
                                                                    IN `in_job` VARCHAR(256), 
                                                                    IN `in_client_comment` TEXT, 
                                                                    IN `in_product_name` VARCHAR(256),
                                                                    IN `in_status` VARCHAR(256),
                                                                    IN `in_product_comment` TEXT)
BEGIN

SET @TMP_VAL := "";
SET @TMP_VAL2 := "";
SET @CLIENT_ID := "";

SET @trimed_in_client_second_name := trim(in_client_second_name);
SET @trimed_in_client_first_name := trim(in_client_first_name);
SET @trimed_in_client_patronymic := trim(in_client_patronymic);
SET @trimed_in_email := trim(in_email);
SET @trimed_in_phone := trim(in_phone);
SET @trimed_in_telegram := trim(in_telegram);
SET @trimed_in_job := trim(in_job);
SET @trimed_in_client_comment := trim(in_client_comment);
SET @trimed_in_product_name := trim(in_product_name);
SET @trimed_in_status := trim(in_status);
SET @trimed_in_product_comment := trim(in_product_comment);


/*создаём клиента*/
/*проверяем не создавали ли этого клиента ранее*/ 
/*пытаемся получить id клиента*/


/*Ищем по полному совпадению ФИО*/
SELECT COUNT(tbl.client_id)
INTO @TMP_VAL
FROM (
/*	
    SELECT cl.client_id as client_id
	FROM clients cl 
	WHERE cl.client_second_name=@trimed_in_client_second_name 
	AND cl.client_first_name=@trimed_in_client_first_name 
	AND cl.client_patronymic=@trimed_in_client_patronymic
	UNION
*/    
	SELECT e.client_id as client_id
	FROM clients_email e 
	WHERE e.email = @trimed_in_email
	UNION
	SELECT p.client_id 
	FROM clients_phone p 
	WHERE p.phone = @trimed_in_phone
	UNION
	SELECT t.client_id 
	FROM clients_telegram t 
	WHERE t.telegram = @trimed_in_telegram
) tbl;
/*INSERT INTO loadlog(loadlog_text) VALUES(CONCAT(@TMP_VAL, ' ДЛЯ Фамилия: ', @trimed_in_client_second_name, ' Имя: ', @trimed_in_client_first_name, ' Отчество: ', @trimed_in_client_patronymic, ' Почта: ', @trimed_in_email, ' Телефон: ', @trimed_in_phone, ' Телеграм: ', @trimed_in_telegram));*/

IF @TMP_VAL = 1 THEN 
	/*Получаем id найденного клиента*/
	SELECT tbl.client_id
	INTO @CLIENT_ID
	FROM (
/*    
		SELECT cl.client_id as client_id
		FROM clients cl 
		WHERE cl.client_second_name=@trimed_in_client_second_name 
		AND cl.client_first_name=@trimed_in_client_first_name 
		AND cl.client_patronymic=@trimed_in_client_patronymic
		UNION
*/        
		SELECT e.client_id as client_id
		FROM clients_email e 
		WHERE e.email = @trimed_in_email
		UNION
		SELECT p.client_id 
		FROM clients_phone p 
		WHERE p.phone = @trimed_in_phone
		UNION
		SELECT t.client_id 
		FROM clients_telegram t 
		WHERE t.telegram = @trimed_in_telegram
	) tbl;    
ELSEIF @TMP_VAL = 0 THEN     
	/*создаём клиента*/
    /*генерим ID клиента */
    SELECT IFNULL(MAX(client_id), 0)+1 as next_id into @CLIENT_ID from clients;
    
	INSERT INTO clients (client_id, client_second_name, client_first_name, client_patronymic, client_job, client_comment) 
	VALUES (@CLIENT_ID, @trimed_in_client_second_name, @trimed_in_client_first_name, @trimed_in_client_patronymic, @trimed_in_job, @trimed_in_client_comment);
	
    /*получаем его ID */
    /*
	SELECT cl.client_id 
    into @CLIENT_ID  
    from clients cl 
    where cl.client_second_name=@trimed_in_client_second_name 
    and cl.client_first_name=@trimed_in_client_first_name 
    and cl.client_patronymic=@trimed_in_client_patronymic;
    */
    
ELSE 
	/* напечатать ошибку*/
	INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Найдено несколько записей с клиентов с реквизитами: ', 'Фамилия: ', @trimed_in_client_second_name, ' Имя: ', @trimed_in_client_first_name, ' Отчество: ', @trimed_in_client_patronymic, ' Почта: ', @trimed_in_email, ' Телефон: ', @trimed_in_phone, ' Телеграм: ', @trimed_in_telegram));
    /*Начинаем схлопывание клинта*/
	CALL united_client(@trimed_in_email, @trimed_in_phone, @trimed_in_telegram, @CLIENT_ID);
    /*CALL mytest(1);*/
    INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Проведена дедубликация. Текущую запись объединили с записью ID: <', @CLIENT_ID, ">"));

    
END IF;


/*записываем e-mail*/
IF @trimed_in_email <> "" THEN
	SELECT COUNT(*)
	INTO @TMP_VAL
	FROM clients_email e WHERE e.email = @trimed_in_email;

	IF @TMP_VAL > 0 THEN
		/*проверяем, что этот email принадлежит нашему клиненту*/
		SELECT COUNT(*)
		INTO @TMP_VAL2
		FROM clients_email e 
		WHERE e.email = @trimed_in_email
		AND e.client_id = @CLIENT_ID;
				

		IF @TMP_VAL2 = 0 THEN
			/* напечатать ошибку*/
			INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Email <', @trimed_in_email, '> не принадлежит клиенту с ID <', @CLIENT_ID, '>'));
		END IF;

	ELSE
		/* создаём запись с email*/
		INSERT INTO clients_email (client_id, email) 
		VALUES (@CLIENT_ID, @trimed_in_email);
	END IF;
END IF;

/*записываем phone*/
IF @trimed_in_phone <> "" THEN
	SELECT COUNT(*)
	INTO @TMP_VAL
	FROM clients_phone e WHERE e.phone = @trimed_in_phone;

	IF @TMP_VAL > 0 THEN
		/*проверяем, что этот номер телефона принадлежит нашему клиненту*/
		SELECT COUNT(*)
		INTO @TMP_VAL2
		FROM clients_phone e 
		WHERE e.phone = @trimed_in_phone
		AND e.client_id = @CLIENT_ID;
				
		IF @TMP_VAL2 = 0 THEN
			/* напечатать ошибку*/
			INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Phone <', @trimed_in_phone, '> не принадлежит клиенту с ID <', @CLIENT_ID, '>'));
		END IF;
		
	ELSE
		/* создаём запись с номером телефона*/
		INSERT INTO clients_phone (client_id, phone) 
		VALUES (@CLIENT_ID, @trimed_in_phone);
	END IF;
END IF;

/*записываем telegram*/
IF @trimed_in_telegram <> "" THEN
	SELECT COUNT(*)
	INTO @TMP_VAL
	FROM clients_telegram e WHERE e.telegram = @trimed_in_telegram;

	IF @TMP_VAL > 0 THEN
		/*проверяем, что этот id telegram принадлежит нашему клиненту*/
		SELECT COUNT(*)
		INTO @TMP_VAL2
		FROM clients_telegram e 
		WHERE e.telegram = @trimed_in_telegram
		AND e.client_id = @CLIENT_ID;
				
		IF @TMP_VAL2 = 0 THEN
			/* напечатать ошибку*/
			INSERT INTO loadlog(loadlog_text) VALUES(CONCAT('Telegram <', @trimed_in_telegram, '> не принадлежит клиенту с ID <', @CLIENT_ID, '>'));
		END IF;
		
	ELSE
		/* создаём запись с номером телефона*/
		INSERT INTO clients_telegram (client_id, telegram) 
		VALUES (@CLIENT_ID, @trimed_in_telegram);
	END IF;
END IF;

/*записываем product*/
IF @trimed_in_product_name <> "" THEN
	/*Опеределяем сколько зписей такого продукта уже есть*/
	SELECT COUNT(*)
	INTO @TMP_VAL
	FROM products t WHERE t.product_name = trim(@trimed_in_product_name);

	IF @TMP_VAL = 0 THEN
		/* создаём запись с продуктом*/
		INSERT INTO products (product_name) 
		VALUES (@trimed_in_product_name);
	END IF;

	/*Проверяем, нет ли такого продукта у клиента*/
	SELECT COUNT(*)
	INTO @TMP_VAL
	FROM sales t 
	WHERE t.client_id = @CLIENT_ID
	AND t.product_id = (SELECT product_id FROM products t WHERE t.product_name = @trimed_in_product_name);

	IF @TMP_VAL = 0 THEN
		/*Делаем запись о продуктах*/
		INSERT INTO sales
		(`client_id`,
		`product_id`,
		`sale_date`,
		`product_comment`)
		VALUES
		(
		@CLIENT_ID,
		(SELECT product_id FROM products t WHERE t.product_name = @trimed_in_product_name),
		'1977-02-04',
		@trimed_in_product_comment
		);
	END IF;
END IF;

END