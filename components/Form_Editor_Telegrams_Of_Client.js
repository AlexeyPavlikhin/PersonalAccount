export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_telegrams: [],
                    detail_client_telegrams_saved: [],
                    detail_client_id: "",
                    WarningMessage: "",
                    dublicate_telegrams: [],
                    is_dublicate: false                    
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorTelegramsOfClient(){
                    document.getElementById("Form_Editor_Telegrams_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.detail_client_telegrams = [];
                    this.detail_client_telegrams_saved = [];                    
                    this.detail_client_id = "";
                    this.WarningMessage = "";
                    this.dublicate_telegrams = [];
                    this.is_dublicate = false;

                    //Получаем актуальные адреса telegram
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_telegrams_by_id.php?clientID=' + clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_telegrams = response.data; 
                            this.detail_client_telegrams_saved = JSON.parse(JSON.stringify(response.data));
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_Telegrams_Of_Client").style.display = "block";

                        } else {
                            console.log('Ответ от сервера пустой (data undefined/null)');
                        }
                    } catch (error) {
                        // Обработка ошибки
                        console.error('Ошибка при запросе:', error);
                        if (error.response) {
                            console.error('Статус ошибки:', error.response.status);
                            console.error('Данные ошибки:', error.response.data);
                        }
                    }
                    
                },
                onClickApplyFormEditorTelegramsOfClient(){
                    
                    let var_client_telegrams_saved = this.detail_client_telegrams_saved;
                    let var_client_telegrams = this.detail_client_telegrams;
                    let var_client_id = this.detail_client_id;

                    //добавляем или обнолвяем адреса
                    var_client_telegrams.forEach(function(item) { 
                        
                        //проверяем не новый ли это адрес
                        if (var_client_telegrams_saved.findIndex((item_saved) => item_saved.telegram_id === item.telegram_id) == -1){
                            //alert("Это новый адрес id: " + item.telegram_id + " telegram: " + item.telegram);

                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/add_client_telegram.php", {telegram: item.telegram, client_id: var_client_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
                                    return true;
                                } else {
                                    alert(response.data);
                                }
                            })
                            .catch(function (error) {
                                alert(error);
                                console.log(error);
                            });
                            

                        } else if (item.telegram != var_client_telegrams_saved[var_client_telegrams_saved.findIndex((item_saved) => item_saved.telegram_id === item.telegram_id)].telegram){ 
                            //значения telegram текущий и сохранённый не равны
                            //alert("надо обновить адрес id: " + item.telegram_id + " telegram: " + item.telegram);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/update_client_telegram_by_id.php", {telegram: item.telegram, telegram_id: item.telegram_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    //this.is_resp_success = true;
                                    //is_resp_success1 = true;
                                    return true;
                                } else {
                                    alert("Удалено "+response.data+" записей");
                                }
                            })
                            .catch(function (error) {
                                alert(error);
                                console.log(error);
                            });
                        }
                    });

                    //удаляем адреса
                    var_client_telegrams_saved.forEach(function(item_saved) {
                        if (var_client_telegrams.findIndex((item) => item.telegram_id === item_saved.telegram_id) == -1){
                            //alert("удалить адрес id: " + item_saved.telegram_id + " telegram: " + item_saved.telegram);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/delete_client_telegram_by_id.php", {telegram_id: item_saved.telegram_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    return true;
                                } else {
                                    alert("Удалено "+response.data+" записей");
                                }
                            })
                            .catch(function (error) {
                                alert(error);
                                console.log(error);
                            });
                     
                        }
                    });
                    
                                       
                    //alert( this.detail_client_telegrams[1].telegram );
                    
                    this.$emit('update_client_data', 'Telegrams');
                    this.onClickCloseFormEditorTelegramsOfClient();

                    
                },

                onClikDeleteDeleteTelegram(in_telegram_id){
                    //this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_telegrams.splice(this.detail_client_telegrams.findIndex((item) => item.telegram_id === in_telegram_id), 1); 
                    this.find_dublicate();
                    this.check_for_change();
                },

                onClickAddTelegram(){
                    //this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_telegrams.push({ telegram: "", telegram_id: Date.now()}); 
                    this.check_for_change();
                },

                onChangeEmail(in_telegram_id){
                    this.find_dublicate();
                    this.check_for_change();
                },

                async find_dublicate(){
                    this.dublicate_telegrams = [];
                    this.is_dublicate = false;

                    for (const entered_telegram of this.detail_client_telegrams) {
                        let sql = "";
                        try {
                            sql = './queries/get_dublicate_of_telegram.php?telegram='+entered_telegram.telegram+'&client_id='+this.detail_client_id;
                            const response = await axios.get(sql);
                            // Обработка успешного ответа
                            if (response.data) {
                            // Далее работаем с данными
                                if(response.data!="") {
                                    for (const row of response.data) {
                                        this.dublicate_telegrams.push({telegram: row.telegram, fio: row.fio});
                                        this.is_dublicate = true;
                                        this.check_for_change();
                                    }
                                }
                            } else {
                                console.log('Ответ от сервера пустой (data undefined/null)');
                            }
                        } catch (error) {
                            // Обработка ошибки
                            console.error('Ошибка при запросе:', error);
                            if (error.response) {
                                console.error('Статус ошибки:', error.response.status);
                                console.error('Данные ошибки:', error.response.data);
                            }
                        }
                    }
                },

                check_for_change(){
                    this.WarningMessage = "";
                    
                    //ищем телефоны из сохраненного массива в актуальном массиве
                    for (const telegram_saved of this.detail_client_telegrams_saved) {
                        //тестируем каждый элемент сохраненного массива
                        if (this.detail_client_telegrams.findIndex((item_actual) => item_actual.telegram === telegram_saved.telegram) == -1){
                            this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                        }
                    }

                    //ищем телефоны актуального  массива в сохраненном массиве 
                    for (const telegram_actual of this.detail_client_telegrams) {
                        //тестируем каждый элемент сохраненного массива
                        if (this.detail_client_telegrams_saved.findIndex((item_saved) => item_saved.telegram === telegram_actual.telegram) == -1){
                            this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                            
                        }
                    }

                    if (this.WarningMessage == ""){
                        document.getElementById("apply_form_editor_telegrams_button").disabled = true;
                    } else {
                        document.getElementById("apply_form_editor_telegrams_button").disabled = false;
                    }

                    for (const telegram_actual of this.detail_client_telegrams) {
                        
                        if (telegram_actual.telegram){
                            //if (!this.validateEmail(telegram_actual.telegram)){
                            if (telegram_actual.telegram.length<2){
                                document.getElementById("apply_form_editor_telegrams_button").disabled = true;
                            }
                        } else {
                            document.getElementById("apply_form_editor_telegrams_button").disabled = true;
                        }
                    }

                    //console.log(this.is_dublicate);
                    if (this.is_dublicate){
                        document.getElementById("apply_form_editor_telegrams_button").disabled = true;
                        //console.log("есть дубликаты");
                    }
                },

                validateEmail(telegram) {
                    var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return re.test(String(telegram).toLowerCase());
                }                

                
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorTelegramsOfClient()">&times;</span>
            <h2>Изменение списка телеграм клиента</h2>
        </div>
        <div class="modal-body">
            <div class="ERROR">{{WarningMessage}}<br/></div>
            <div class="container_inline" v-for="detail_client_telegram in detail_client_telegrams">
                <input class="msll_filter" type="input" v-model="detail_client_telegram.telegram" @input="onChangeEmail(detail_client_telegram.telegram_id)"/>
                <input type="button" value = "&times;" @click='onClikDeleteDeleteTelegram(detail_client_telegram.telegram_id)'>
            </div>
            <button class="msll_middle_button" type="button" @click="onClickAddTelegram()">Добавить телеграм</button>
            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorTelegramsOfClient()" id="apply_form_editor_telegrams_button" disabled >Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorTelegramsOfClient()">Отменить</button>

            <table class='msll_table'>
                <tbody>
                    <tr v-for="dublicate_telegram in dublicate_telegrams">
                        <td> {{dublicate_telegram.telegram}}</td>
                        <td class="ERROR">принадлежит клиенту</td>
                        <td> {{dublicate_telegram.fio}}</td>
                    </tr>
                </tbody>
            </table>              

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
