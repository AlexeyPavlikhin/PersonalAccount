CREATE DEFINER=`root`@`localhost` PROCEDURE `concat_clients_property`(IN `in_client_id` INT,
																	  IN `in_client_last_name` VARCHAR(250),
																	  IN `in_client_first_name` VARCHAR(250),
																	  IN `in_client_patronymic` VARCHAR(250),
																	  IN `in_client_job` VARCHAR(250),
																	  IN `in_client_comment` TEXT)
BEGIN
DECLARE v_tmp_int INT;
DECLARE v_tmp_varchar VARCHAR(250);
DECLARE v_tmp_text TEXT;

/*Определяем необходимость обновления Фамилии*/
IF in_client_last_name <> "" THEN
	SELECT COUNT(*) INTO v_tmp_int FROM clients c WHERE c.client_id = in_client_id AND c.client_last_name LIKE CONCAT("%", in_client_last_name,"%");
	IF v_tmp_int = 0 THEN
		SELECT c.client_last_name INTO v_tmp_varchar FROM clients c WHERE c.client_id = in_client_id;
		UPDATE clients 
		SET client_last_name = TRIM(CONCAT(v_tmp_varchar, " ", in_client_last_name))
		WHERE client_id = in_client_id;
	END IF; 
END IF; 

/*Определяем необходимость обновления Имя*/
IF in_client_first_name <> "" THEN
	SELECT COUNT(*) INTO v_tmp_int FROM clients c WHERE c.client_id = in_client_id AND c.client_first_name LIKE CONCAT("%", in_client_first_name,"%");
	IF v_tmp_int = 0 THEN
		SELECT c.client_first_name INTO v_tmp_varchar FROM clients c WHERE c.client_id = in_client_id;
		UPDATE clients 
		SET client_first_name = TRIM(CONCAT(v_tmp_varchar, " ", in_client_first_name))
		WHERE client_id = in_client_id;
	END IF; 
END IF; 
/*Определяем необходимость обновления Отчество*/
IF in_client_patronymic <> "" THEN
	SELECT COUNT(*) INTO v_tmp_int FROM clients c WHERE c.client_id = in_client_id AND c.client_patronymic LIKE CONCAT("%", in_client_patronymic,"%");
	IF v_tmp_int = 0 THEN
		SELECT c.client_patronymic INTO v_tmp_varchar FROM clients c WHERE c.client_id = in_client_id;
		UPDATE clients 
		SET client_patronymic = TRIM(CONCAT(v_tmp_varchar, " ", in_client_patronymic))
		WHERE client_id = in_client_id;
	END IF; 
END IF; 

/*Определяем необходимость обновления места работы */
IF in_client_job <> "" THEN
	SELECT COUNT(*) INTO v_tmp_int FROM clients c WHERE c.client_id = in_client_id AND c.client_job LIKE CONCAT("%", in_client_job,"%");
	IF v_tmp_int = 0 THEN
		SELECT c.client_job INTO v_tmp_varchar FROM clients c WHERE c.client_id = in_client_id;
		UPDATE clients 
		SET client_job = TRIM(CONCAT(v_tmp_varchar, " ", in_client_job))
		WHERE client_id = in_client_id;
	END IF; 
END IF;

/*Определяем необходимость обновления комментария клиента */
IF in_client_comment <> "" THEN
	SELECT COUNT(*) INTO v_tmp_int FROM clients c WHERE c.client_id = in_client_id AND c.client_comment LIKE CONCAT("%", in_client_comment,"%");
	IF v_tmp_int = 0 THEN 
		SELECT c.client_comment INTO v_tmp_text FROM clients c WHERE c.client_id = in_client_id;
		UPDATE clients 
		SET client_comment = TRIM(CONCAT(v_tmp_text, " ", in_client_comment))
		WHERE client_id = in_client_id;
	END IF;
END IF;

END