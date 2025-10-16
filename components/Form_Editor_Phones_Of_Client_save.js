export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_phones: "",
                    detail_client_phones_saved: "",
                    detail_client_id: "",
                    WarningMessage: ""
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorPhonesOfClient(){
                    document.getElementById("Form_Editor_Phones_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.WarningMessage = "";

                    //Получаем актуальные телефоны
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_phones_by_id.php?clientID=' + clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_phones = response.data; 
                            this.detail_client_phones_saved = JSON.parse(JSON.stringify(response.data));
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_Phones_Of_Client").style.display = "block";

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
                onClickApplyFormEditorPhonesOfClient(){
                    
                    let var_client_phones_saved = this.detail_client_phones_saved;
                    let var_client_phones = this.detail_client_phones;
                    let var_client_id = this.detail_client_id;

                    //добавляем или обнолвяем адреса
                    var_client_phones.forEach(function(item) { 
                        
                        //проверяем не новый ли это адрес
                        if (var_client_phones_saved.findIndex((item_saved) => item_saved.phone_id === item.phone_id) == -1){
                            //alert("Это новый адрес id: " + item.phone_id + " phone: " + item.phone);

                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/add_client_phone.php", {phone: item.phone, client_id: var_client_id})
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
                            

                        } else if (item.phone != var_client_phones_saved[var_client_phones_saved.findIndex((item_saved) => item_saved.phone_id === item.phone_id)].phone){ 
                            //значения phone текущий и сохранённый не равны
                            //alert("надо обновить адрес id: " + item.phone_id + " phone: " + item.phone);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/update_client_phone_by_id.php", {phone: item.phone, phone_id: item.phone_id})
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
                    var_client_phones_saved.forEach(function(item_saved) {
                        if (var_client_phones.findIndex((item) => item.phone_id === item_saved.phone_id) == -1){
                            //alert("удалить адрес id: " + item_saved.phone_id + " phone: " + item_saved.phone);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/delete_client_phone_by_id.php", {phone_id: item_saved.phone_id})
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
                    
                                       
                    //alert( this.detail_client_phones[1].phone );
                    
                    this.$emit('update_client_data', 'Phones');
                    this.onClickCloseFormEditorPhonesOfClient();

                    
                },
                onClikDeleteDeletePhone(in_phone_id){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_phones.splice(this.detail_client_phones.findIndex((item) => item.phone_id === in_phone_id), 1); 



                },
                onClickAddPhone(){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_phones.push({ phone: "", phone_id: Date.now()}); 
                }
                
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-40">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorPhonesOfClient()">&times;</span>
            <h2>Изменение списка телефонов клиента</h2>
        </div>
        <div class="modal-body">
            <div class="ERROR">{{WarningMessage}}<br/></div>
            <button class="msll_middle_button" type="button" @click="onClickAddPhone()">Добавить телефон</button>
            <div class="container_inline" v-for="detail_client_phone in detail_client_phones">
                <input class="msll_filter" type="input" v-model="detail_client_phone.phone"/>
                <input type="button" value = "&times;" @click='onClikDeleteDeletePhone(detail_client_phone.phone_id)'>
            </div>

            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorPhonesOfClient()">Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorPhonesOfClient()">Отменить</button>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
