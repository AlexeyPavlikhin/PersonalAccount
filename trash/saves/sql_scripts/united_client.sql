CREATE DEFINER=`root`@`localhost` PROCEDURE `united_client`(IN `in_email` VARCHAR(100), 
															IN `in_phone` VARCHAR(100), 
                                                            IN `in_telegram` VARCHAR(100), 
                                                            OUT `out_client_id` INT)
BEGIN

DECLARE terminate INT DEFAULT FALSE;
Declare v_wrong_client_id int;
Declare v_comment_wrong_client TEXT;
Declare v_save_comment_client TEXT;
Declare v_tmp_email_id int;
Declare v_tmp_email varchar(100);
Declare v_tmp_phone_id int;
Declare v_tmp_phone varchar(100);
Declare v_tmp_telegram_id int;
Declare v_tmp_telegram varchar(100);
Declare v_tmp_sales_id int;
Declare v_tmp_sales_product_id int;

Declare v_client_last_name varchar(250);
Declare v_client_first_name varchar(250);
Declare v_client_patronymic varchar(250);
Declare v_client_job varchar(250);
Declare v_client_comment TEXT;


Declare v_tmp_val varchar(25);
Declare CLIDCursor Cursor for SELECT tbl.client_id FROM (	
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
								WHERE t.telegram = in_telegram) tbl
							  WHERE tbl.client_id <> out_client_id;
Declare EmailsCursor Cursor for SELECT e.email_id, e.email FROM clients_email e where e.client_id = v_wrong_client_id;
Declare PhonesCursor Cursor for SELECT p.phone_id, p.phone FROM clients_phone p where p.client_id = v_wrong_client_id;
Declare TelegramsCursor Cursor for SELECT t.telegram_id, t.telegram FROM clients_telegram t where t.client_id = v_wrong_client_id;
Declare SalesCursor Cursor for SELECT s.id, s.product_id FROM sales s where s.client_id = v_wrong_client_id;
                              
DECLARE CONTINUE HANDLER FOR NOT FOUND SET terminate = true;


    /*Получаем минимальный ID. Именно он и останется за клиентом*/
	SELECT MIN(tbl.client_id) INTO out_client_id FROM (	
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
    
    /*Обрабатываем все остльные ID (задублированных записей о клинете) */
	OPEN CLIDCursor;
	getIDs: LOOP
		FETCH CLIDCursor INTO v_wrong_client_id;
		IF terminate = TRUE THEN
			LEAVE getIDs;
		END IF;
                
		SET v_comment_wrong_client = "";
        
        /*обрабатываем все email*/
		OPEN EmailsCursor;
			getEMLs: LOOP
				FETCH EmailsCursor INTO v_tmp_email_id, v_tmp_email;
				IF terminate = TRUE THEN
					LEAVE getEMLs;
				END IF;        
				
                update clients_email SET client_id = out_client_id where email_id = v_tmp_email_id;
                SET v_comment_wrong_client = concat("Email: <", v_tmp_email, "> email_id: <", v_tmp_email_id, "> перепривязан к клиенту id <", out_client_id, ">;");
                
                
		END LOOP getEMLs;
		CLOSE EmailsCursor;        
        SET terminate = false;        
        
        /*обрабатываем все телефоны*/
		OPEN PhonesCursor;
			getPHNs: LOOP
				FETCH PhonesCursor INTO v_tmp_phone_id, v_tmp_phone;
				IF terminate = TRUE THEN
					LEAVE getPHNs;
				END IF;        
				
                update clients_phone SET client_id = out_client_id where phone_id = v_tmp_phone_id;
                SET v_comment_wrong_client = concat(v_comment_wrong_client," Телефон: <", v_tmp_phone, "> phone_id: <", v_tmp_phone_id, "> перепривязан к клиенту id <", out_client_id, ">;");
                                
		END LOOP getPHNs;
		CLOSE PhonesCursor;        
        SET terminate = false;        
        
		/*обрабатываем все telegram*/
		OPEN TelegramsCursor;
			getTLGs: LOOP
				FETCH TelegramsCursor INTO v_tmp_telegram_id, v_tmp_telegram;
				IF terminate = TRUE THEN
					LEAVE getTLGs;
				END IF;        
				
                update clients_telegram SET client_id = out_client_id where telegram_id = v_tmp_telegram_id;
                SET v_comment_wrong_client = concat(v_comment_wrong_client," Телеграм: <", v_tmp_telegram, "> telegram_id: <", v_tmp_telegram_id, "> перепривязан к клиенту id <", out_client_id, ">;");
                                
		END LOOP getTLGs;
		CLOSE TelegramsCursor;        
        SET terminate = false;         
        
		/*обрабатываем все продажи*/
		OPEN SalesCursor;
			getSLs: LOOP
				FETCH SalesCursor INTO v_tmp_sales_id, v_tmp_sales_product_id;
				IF terminate = TRUE THEN
					LEAVE getSLs;
				END IF;        
				
                update sales SET client_id = out_client_id where id = v_tmp_sales_id;
                SET v_comment_wrong_client = concat(v_comment_wrong_client," Продажа ID: <", v_tmp_sales_id, "> product_id: <", v_tmp_sales_product_id, "> перепривязана к клиенту id <", out_client_id, ">;");
                                
		END LOOP getSLs;
		CLOSE SalesCursor;        
        SET terminate = false;       
        
		/*Добавлем к основной записи информацию из дублирующей записи*/
		SELECT 
			   c.client_first_name,
			   c.client_last_name,
			   c.client_patronymic,
			   c.client_job,
			   c.client_comment
		INTO
 			   v_client_first_name,
               v_client_last_name,
			   v_client_patronymic,
			   v_client_job,
			   v_client_comment        
		FROM clients c
        WHERE c.client_id = v_wrong_client_id;
        
		CALL concat_clients_property (out_client_id, 
									  v_client_last_name,
									  v_client_first_name,
									  v_client_patronymic,
									  v_client_job,
									  v_client_comment);        
        
        
        /*Добовляем к ошибочной записи информацию о том, с какой записью объединили (типа протокол)*/
        SELECT IFNULL(c.client_comment, "") INTO v_save_comment_client FROM clients c WHERE c.client_id = v_wrong_client_id;
        update clients SET client_comment = concat(v_save_comment_client , "; ", v_comment_wrong_client) where client_id = v_wrong_client_id;
        
	END LOOP getIDs;
	CLOSE CLIDCursor;
    
END