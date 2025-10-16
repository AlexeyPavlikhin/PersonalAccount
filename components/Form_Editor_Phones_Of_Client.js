export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_phones: [],
                    detail_client_phones_saved: [],
                    detail_client_id: "",
                    WarningMessage: "",
                    dublicate_phones: [],
                    is_dublicate: false
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorPhonesOfClient(){
                    document.getElementById("Form_Editor_Phones_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.detail_client_phones = [];
                    this.detail_client_phones_saved = [];                    
                    this.detail_client_id = "";
                    this.WarningMessage = "";
                    this.dublicate_phones = [];
                    this.is_dublicate = false;

                    //Получаем актуальные телефоны
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_phones_by_id.php?clientID=' + clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_phones = response.data; 
                            
                            //отформатировать все полученные телефоны
                            this.detail_client_phones.forEach((phone_and_id) => {
                                phone_and_id.phone = this.formate_phone2(phone_and_id.phone);
                            });
                            this.detail_client_phones_saved = JSON.parse(JSON.stringify(this.detail_client_phones));

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
                    let this1 = this;

                    //добавляем или обнолвяем адреса
                    var_client_phones.forEach(function(item) { 
                        
                        //проверяем не новый ли это адрес
                        if (var_client_phones_saved.findIndex((item_saved) => item_saved.phone_id === item.phone_id) == -1){
                            //alert("Это новый адрес id: " + item.phone_id + " phone: " + item.phone);

                            axios.post("./queries/add_client_phone.php", {phone: this1.to_clear_number(item.phone), client_id: var_client_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
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
                            axios.post("./queries/update_client_phone_by_id.php", {phone: this1.to_clear_number(item.phone), phone_id: item.phone_id})
                            .then(function (response) {
                                //console.log(response.data);
                                if (response.data=="1"){
                                    return true;
                                } else {
                                    alert("Обновлено "+response.data+" записей");
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
                    //this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_phones.splice(this.detail_client_phones.findIndex((item) => item.phone_id === in_phone_id), 1); 
                    this.find_dublicate();
                    this.check_for_change();
                },

                onClickAddPhone(){
                    //this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_phones.push({ phone: "", phone_id: Date.now()}); 
                    this.check_for_change();
                },

                format_phone(in_phone){
                    let ret;
                    //console.log(in_phone.length);

                    in_phone=in_phone.toString().slice();
                    
                    while (in_phone.length<11){
                        in_phone  = in_phone + "_"
                    }
                    
                    //console.log(in_phone);

                    ret="-"+in_phone.slice(-2);
                    in_phone=in_phone.slice(0,-2);

                    ret="-"+in_phone.slice(-2)+ret;
                    in_phone=in_phone.slice(0,-2);

                    ret=") "+in_phone.slice(-3)+ret;
                    in_phone=in_phone.slice(0,-3);

                    ret=" ("+in_phone.slice(-3)+ret;
                    in_phone=in_phone.slice(0,-3);                

                    ret="+"+in_phone+ret;
                    
                    return ret;
                },
                onChangePhone(in_phone_id){
                    this.detail_client_phones[this.detail_client_phones.findIndex((item) => item.phone_id === in_phone_id)].phone =
                    this.formate_phone2(this.to_clear_number(this.detail_client_phones[this.detail_client_phones.findIndex((item) => item.phone_id === in_phone_id)].phone));
                    this.find_dublicate();
                    this.check_for_change();
                },                

                formate_phone2(in_phone){
                    if (!in_phone){
                        in_phone="";
                    }

                    let ret;
                    let v_count = 0;
                    
                    in_phone.split('').forEach((item) => {
                        v_count++
                        if (v_count==1){
                            //console.log(v_count);
                            if(item == "8"){
                                ret="+7";
                            }else if(item == "0"){
                                ret="";
                            } else {
                                ret="+"+item;
                            }
                        }

                        if (v_count==2){
                            //console.log(v_count);
                            ret=ret + " (" + item;
                        }

                        if (v_count==3){
                            //console.log(v_count);
                            ret=ret + item;
                        }

                        if (v_count==4){
                            //console.log(v_count);
                            ret=ret + item;
                        }

                        if (v_count==5){
                            //console.log(v_count);
                            ret=ret + ") "+ item;
                        }

                        if (v_count==6){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                        if (v_count==7){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                        if (v_count==8){
                            //console.log(v_count);
                            ret=ret + "-" + item;
                        }
                        if (v_count==9){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                        if (v_count==10){
                            //console.log(v_count);
                            ret=ret + "-" + item;
                        }
                        if (v_count==11){
                            //console.log(v_count);
                            ret=ret + item;
                        }
                    })
                    return ret;
                },
                to_clear_number(in_str){
                    let v_new_value = "";                    
                    if (in_str){
                        if (in_str!=""){
                            in_str.split('').forEach((item) => {
                                if (Number.parseInt(item) || item=="0"){
                                    v_new_value=v_new_value + item
                                } 
                            })
                            
                        }
                    }
                    return v_new_value;
                },
                async find_dublicate(){
                    this.dublicate_phones = [];
                    this.is_dublicate = false;
                    //console.log("поставили false")

                    for (const entered_phone of this.detail_client_phones) {
                        
                        // параметры поиска
                        let sql = "";
                        try {

                            sql = './queries/get_dublicate_of_phone.php?phone='+this.to_clear_number(entered_phone.phone)+'&client_id='+this.detail_client_id;
                            const response = await axios.get(sql);

                            // Обработка успешного ответа
                            if (response.data) {
                            // Далее работаем с данными
                                if(response.data!="") {
                                    for (const row of response.data) {
                                        this.dublicate_phones.push({phone: row.phone, fio: row.fio});
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
                    for (const phone_saved of this.detail_client_phones_saved) {
                        //тестируем каждый элемент сохраненного массива
                        if (this.detail_client_phones.findIndex((item_actual) => item_actual.phone === phone_saved.phone) == -1){
                            this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                        }
                    }

                    //ищем телефоны актуального  массива в сохраненном массиве 
                    for (const phone_actual of this.detail_client_phones) {
                        //тестируем каждый элемент сохраненного массива
                        if (this.detail_client_phones_saved.findIndex((item_saved) => item_saved.phone === phone_actual.phone) == -1){
                            this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                            
                        }
                    }

                    if (this.WarningMessage == ""){
                        document.getElementById("apply_form_editor_phones_button").disabled = true;
                    } else {
                        document.getElementById("apply_form_editor_phones_button").disabled = false;
                    }

                    for (const phone_actual of this.detail_client_phones) {
                        
                        if (phone_actual.phone){
                            if (phone_actual.phone.length < 18){
                                document.getElementById("apply_form_editor_phones_button").disabled = true;
                            }
                        } else {
                            document.getElementById("apply_form_editor_phones_button").disabled = true;
                        }
                    }

                    //console.log(this.is_dublicate);
                    if (this.is_dublicate){
                        document.getElementById("apply_form_editor_phones_button").disabled = true;
                        //console.log("есть дубликаты");
                    }

                    //console.log(this.dublicate_phones);
                    //console.log(JSON.stringify(this.dublicate_phones));
                    //console.log(JSON.stringify(this.dublicate_phones2));
                    //console.log(JSON.parse(JSON.stringify(this.dublicate_phones)));

                    //console.log(this.detail_client_phones);
                    //console.log(JSON.stringify(this.detail_client_phones));
                    //JSON.parse(JSON.stringify(response.data))


                    //this.dublicate_phones.forEach(function(item) { 
                        //console.log(item.phone);
                    //})

                    //let array = Array.from(this.dublicate_phones);

                    /*
                    for (const dublicate_phone of this.dublicate_phones2){
                        document.getElementById("apply_form_editor_phones_button").disabled = true;
                        console.log("|"+dublicate_phone.phone+"|");
                    }
                    */
                        

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
            
            
            <div class="container_inline" v-for="detail_client_phone in detail_client_phones">
                <input class="msll_filter" type="text" v-model="detail_client_phone.phone" placeholder="+7 (916) 123-45-67" @input="onChangePhone(detail_client_phone.phone_id)"/>
                <input type="button" value = "&times;" @click="onClikDeleteDeletePhone(detail_client_phone.phone_id)">
            </div>

            <button class="msll_middle_button" type="button" @click="onClickAddPhone()">Добавить телефон</button>
            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorPhonesOfClient()" id="apply_form_editor_phones_button" disabled >Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorPhonesOfClient()">Отменить</button>

            <table class='msll_table'>
                <tbody>
                    <tr v-for="dublicate_phone in dublicate_phones">
                        <td> {{dublicate_phone.phone}}</td>
                        <td class="ERROR">принадлежит клиенту</td>
                        <td> {{dublicate_phone.fio}}</td>
                    </tr>
                </tbody>
            </table>    

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
