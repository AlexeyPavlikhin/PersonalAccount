export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_telegrams: "",
                    detail_client_telegrams_saved: "",
                    detail_client_id: "",
                    WarningMessage: ""
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorTelegramsOfClient(){
                    document.getElementById("Form_Editor_Telegrams_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.WarningMessage = "";

                    //Получаем актуальные адреса telegram
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_telegrams_by_id.php?clientID=' + clientID);
                        if (response.data) {
                            console.log(response.data);
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
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_telegrams.splice(this.detail_client_telegrams.findIndex((item) => item.telegram_id === in_telegram_id), 1); 



                },
                onClickAddTelegram(){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_telegrams.push({ telegram: "", telegram_id: Date.now()}); 
                }
                
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-editor">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorTelegramsOfClient()">&times;</span>
            <h2>Изменение списка телеграм клиента</h2>
        </div>
        <div class="modal-body">
            <div class="ERROR">{{WarningMessage}}<br/></div>
            <button class="msll_middle_button" type="button" @click="onClickAddTelegram()">Добавить телеграм</button>
            <div class="container_inline" v-for="detail_client_telegram in detail_client_telegrams">
                <input class="msll_filter" type="input" v-model="detail_client_telegram.telegram"/>
                <input type="button" value = "&times;" @click='onClikDeleteDeleteTelegram(detail_client_telegram.telegram_id)'>
            </div>

            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorTelegramsOfClient()">Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorTelegramsOfClient()">Отменить</button>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
