                $.ajax({
                    url: './queries/get_all_items_of_selected_object.php',         /* Куда пойдет запрос */
                    method: 'get',             /* Метод передачи (post или get) */
                    dataType: 'html',          /* Тип данных в ответе (xml, json, script, html). */
                    data: {text: 'Текст'},     /* Параметры передаваемые в запросе. */
                    success: function(data){   /* функция которая будет выполнена после успешного запроса.  */
                        alert(data);            /* В переменной data содержится ответ от index.php. */
                        //options_values=data;
                        //location.href = 'uc.php';
                        //options_values = "[{ message: 'Ford' }, { message: 'BMW' }, { message: 'Fiat' }]";
                        //this.options_values = null;
                        
                        // Передача данных через callback
                        processData(data);
                    }
                });