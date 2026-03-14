import NavigationMenu from './Navigation_Menu.js';    
import DetailInfoOfClient from './detail_info_of_client.js';
import FormCreateNewClient from './Form_Create_New_Client.js';

export default {
        props: ['message'],
        components: {
            DetailInfoOfClient,
            FormCreateNewClient,
            NavigationMenu
        },
        data() {
            return {
                opt: 0,

                options1: [
                    { text: 'ФИО', value: 'ФИО' },
                    { text: 'Продукт', value: 'Продукт' },
                    { text: 'Место работы', value: 'Место работы' },
                    { text: 'Статус продажи', value: 'Статус продажи' }
                ],

                options2: [
                    { text: 'Содержит', value: 'Содержит' },
                    { text: 'Не содержит', value: 'Не содержит' },
                    { text: 'Совпадает', value: 'Совпадает' },
                    { text: 'Не совпадает', value: 'Не совпадает' }
                ],
                
                items_for_search: [],
                conditions: [],
                list_of_clients: [],
                p_color: "#bd162b",
                p_size: "20px",
                current_route_name: "sales"


            }
        },
        async mounted() {
            this.$root.check_for_permition_route(this.current_route_name);
            this.$root.$refs.ref_NavigationMenu.setActivMenuItem(this.current_route_name);
            this.onSelectFirstFilter();
            try {
                    
                    //запускаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "block"; 

                    const response = await axios.get('./queries/get_list_of_cliets_by_conditions.php?conditions='+encodeURIComponent(JSON.stringify(this.conditions)));
                    
                    //останавливаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "none";

                    if (response.data) {
                        //console.log(response.data);
                        if (response.data[0]+response.data[1]+response.data[2] != "<br"){
                            this.list_of_clients=response.data;
                        } else {
                            console.error('Ошибка в ответе от сервера');
                            console.log(response.data);
                            this.list_of_clients = "";
                        }                        
                    } else {
                        // пустой ответ
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
        methods: {
            async onSelectFirstFilter(){
                // Assuming you have a select element with id="mySelect"
                const selectElement = document.getElementById('fieldFirstFilter');
                // Get the value of the currently selected option
                let url;

                if (selectElement.value=="ФИО"){
                    url="./queries/get_all_fio.php";
                } else if (selectElement.value=="Продукт"){
                    url="./queries/get_all_products.php";
                } else if (selectElement.value=="Место работы"){
                    url="./queries/get_all_jobs.php";
                } else if (selectElement.value=="Статус продажи"){
                    url="./queries/get_all_sales_status.php";
                }
                try {
                    const response = await axios.get(url);

                    // Обработка успешного ответа
                    if (response.data) {
                        // Далее работаем с данными
                        this.items_for_search = [];
                        for (const obj of response.data) {
                            this.items_for_search.push(obj.data_for_filter);
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
                if (document.getElementById('item_for_search')){
                    document.getElementById('item_for_search').value="";
                }   
                
            },
            async onClikBtnApply(is_need_add_condition){
                
                if (is_need_add_condition){
                    if (document.getElementById('item_for_search').value != ''){
                        this.conditions.push({ object: document.getElementById('fieldFirstFilter').value, operation: document.getElementById('search_operation').value, value: document.getElementById('item_for_search').value, item_id: this.conditions.length}); 
                    }
                }

                try {
                    //запускаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "block"; 

                    //console.log(encodeURIComponent(JSON.stringify(this.conditions)));
                    //console.log(JSON.stringify(this.conditions));
                    const response = await axios.get('./queries/get_list_of_cliets_by_conditions.php?conditions='+encodeURIComponent(JSON.stringify(this.conditions)));
                    //const response = await axios.get('./queries/get_list_of_cliets_by_conditions.php?conditions='+JSON.stringify(this.conditions));
                    //const response = await axios.get('./queries/get_list_of_cliets_by_conditions.php', conditions, {headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
                    
                    //останавливаем спиннер    
                    document.getElementById("id_spinner_panel").style.display = "none";

                    // Обработка успешного ответа
                    if (response.data) {
                        //console.log(response.data);
                        if (response.data[0]+response.data[1]+response.data[2] != "<br"){
                            this.list_of_clients=response.data;
                        } else {
                            console.error('Ошибка в ответе от сервера');
                            console.log(response.data);
                            this.list_of_clients = "";
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
                
                document.getElementById('item_for_search').value="";

            },
            onClikDeleteItemOfConditions(id){
                this.conditions.splice(this.conditions.findIndex((item) => item.item_id === id), 1); 
                this.onClikBtnApply(false);

            },
            async onClikClientDetail(clientID){
                this.$refs.childRef.onClikClientDetail(clientID);

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("form_Detail_Info_Of_Client").style.display = "block";

                //console.log("onClikClientDetail")
            },
            formate_phone(in_phone){
                let ret;
                let in_phone2;
                in_phone2 = in_phone;

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
            onChangeClientData(){
                //alert("получен event onChangeClientData на форме sales.php")
                this.onSelectFirstFilter();
                this.onClikBtnApply(true);
            },
            onClikCreateNewClient(){

                this.$refs.FormCreateNewClientRef.activate();

                //отключить прокрутку страницы
                document.body.style.overflow = 'hidden';

                //сделать элемент модальным     
                document.getElementById("form_Create_New_Client").style.display = "block";
            }, 
            onClientCreted(in_ClientID){
                //alert("3: "+in_ClientID);
                this.onChangeClientData();
                this.onClikClientDetail(in_ClientID);

            },
            createTGLink(inLink){
                return "https://t.me/"+inLink.replaceAll("@", "");
            },
            createMTLink(inLink){
                return "mailto:"+inLink;
            },
            createTelLink(inLink){
                return "tel:+"+inLink;
            },
            onClikBtnTestPost(){
                //let v_json_param = '{"Name":"u0410u043bu0435u043au0441u0435u0439","Name_2":"u041fu0430u0432u043bu0438u0445u0438u043d","Name_3":"AlexeyPavlikhin","Email":"pavlikhin@gmail.com","Phone":"+7 (903) 101-89-37","Checkbox":"yes","payment":{"sys":"tinkoff","systranid":"7659818103","orderid":"1448373555","products":[{"name":"u0414u043eu0441u0442u0443u043f u043a u0442u0435u0441u0442u0443 u043du0430 1 u0447u0435u043b.","quantity":1,"amount":5,"price":"5"}],"amount":"5"},"formid":"form644050497","formname":"Cart","API-key":"D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT"}';
                let v_json_param = "Name=%D0%90%D0%BB%D0%B5%D0%BA%D1%81%D0%B5%D0%B9&Name_2=%D0%9F%D0%B0%D0%B2%D0%BB%D0%B8%D1%85%D0%B8%D0%BD&Name_3=AlexeyPavlihin&Email=pavlikhin%2B17%40gmail.com&Phone=%2B7+%28903%29+101-89-37&Textarea=%D0%9D%D0%B5%D1%82+%D0%B2%D0%BE%D0%BF%D1%80%D0%BE%D1%81%D0%BE%D0%B2&Checkbox=yes&payment%5Bsys%5D=tinkoff&payment%5Bsystranid%5D=7695032769&payment%5Borderid%5D=1992217563&payment%5Bproducts%5D%5B0%5D%5Bname%5D=%D0%94%D0%BE%D1%81%D1%82%D1%83%D0%BF+%D0%BA+%D1%82%D0%B5%D1%81%D1%82%D1%83+%D0%BD%D0%B0+1+%D1%87%D0%B5%D0%BB.&payment%5Bproducts%5D%5B0%5D%5Bquantity%5D=1&payment%5Bproducts%5D%5B0%5D%5Bamount%5D=5&payment%5Bproducts%5D%5B0%5D%5Bprice%5D=5&payment%5Bamount%5D=5&formid=form644050497&formname=Cart&API-key=D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT";

                //axios.post("./api/payment_event.php", {Name:"Алексей", telegram: "my_telegram", client_id: "client ID"})                
                axios.post("./api/payment_event.php", v_json_param)
                //axios.post("./api/test_call_procedure.php", v_json_param)
                .then(function (response) {
                    console.log(response.data);
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
            },
            onClikBtnTestPost2(){
                //let v_json_param = '{"Name":"u0410u043bu0435u043au0441u0435u0439","Name_2":"u041fu0430u0432u043bu0438u0445u0438u043d","Name_3":"AlexeyPavlikhin","Email":"pavlikhin@gmail.com","Phone":"+7 (903) 101-89-37","Checkbox":"yes","payment":{"sys":"tinkoff","systranid":"7659818103","orderid":"1448373555","products":[{"name":"u0414u043eu0441u0442u0443u043f u043a u0442u0435u0441u0442u0443 u043du0430 1 u0447u0435u043b.","quantity":1,"amount":5,"price":"5"}],"amount":"5"},"formid":"form644050497","formname":"Cart","API-key":"D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT"}';
                //let v_json_param = "Name=%D0%90%D0%BB%D0%B5%D0%BA%D1%81%D0%B5%D0%B9&Name_2=%D0%9F%D0%B0%D0%B2%D0%BB%D0%B8%D1%85%D0%B8%D0%BD&Name_3=AlexeyPavlihin&Email=pavlikhin%2B17%40gmail.com&Phone=%2B7+%28903%29+101-89-37&Textarea=%D0%9D%D0%B5%D1%82+%D0%B2%D0%BE%D0%BF%D1%80%D0%BE%D1%81%D0%BE%D0%B2&Checkbox=yes&payment%5Bsys%5D=tinkoff&payment%5Bsystranid%5D=7695032769&payment%5Borderid%5D=1992217563&payment%5Bproducts%5D%5B0%5D%5Bname%5D=%D0%94%D0%BE%D1%81%D1%82%D1%83%D0%BF+%D0%BA+%D1%82%D0%B5%D1%81%D1%82%D1%83+%D0%BD%D0%B0+1+%D1%87%D0%B5%D0%BB.&payment%5Bproducts%5D%5B0%5D%5Bquantity%5D=1&payment%5Bproducts%5D%5B0%5D%5Bamount%5D=5&payment%5Bproducts%5D%5B0%5D%5Bprice%5D=5&payment%5Bamount%5D=5&formid=form644050497&formname=Cart&API-key=D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT";

                //axios.post("./api/payment_event.php", {Name:"Алексей", telegram: "my_telegram", client_id: "client ID"})                
                axios.post("./queries/get_current_user_name.php")
                //axios.post("./api/test_call_procedure.php", v_json_param)
                .then(function (response) {
                    console.log(response.data);
                    if (response.data==""){
                        //this.is_resp_success = true;
                        //is_resp_success1 = true;
                        alert("Пусто");
                        window.location.href = '/login.php';
                        return true;
                    } else {
                        alert(response.data);
                        //window.location.href = '/login.php';
                    }
                })
                .catch(function (error) {
                    //alert(error);
                    console.log(error);
                    window.location.href = '/login.php';
                });
            },            
            onClikBtnTestPost3(){
                this.$root.check_for_empty_session();
                //this.$root.$router.push('/login');
                //this.$root.$router.push({path: '/login'});
                //window.location.href = '/login.php';
                //window.location.replace();
/*                
                //let v_json_param = '{"Name":"u0410u043bu0435u043au0441u0435u0439","Name_2":"u041fu0430u0432u043bu0438u0445u0438u043d","Name_3":"AlexeyPavlikhin","Email":"pavlikhin@gmail.com","Phone":"+7 (903) 101-89-37","Checkbox":"yes","payment":{"sys":"tinkoff","systranid":"7659818103","orderid":"1448373555","products":[{"name":"u0414u043eu0441u0442u0443u043f u043a u0442u0435u0441u0442u0443 u043du0430 1 u0447u0435u043b.","quantity":1,"amount":5,"price":"5"}],"amount":"5"},"formid":"form644050497","formname":"Cart","API-key":"D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT"}';
                //let v_json_param = "Name=%D0%90%D0%BB%D0%B5%D0%BA%D1%81%D0%B5%D0%B9&Name_2=%D0%9F%D0%B0%D0%B2%D0%BB%D0%B8%D1%85%D0%B8%D0%BD&Name_3=AlexeyPavlihin&Email=pavlikhin%2B17%40gmail.com&Phone=%2B7+%28903%29+101-89-37&Textarea=%D0%9D%D0%B5%D1%82+%D0%B2%D0%BE%D0%BF%D1%80%D0%BE%D1%81%D0%BE%D0%B2&Checkbox=yes&payment%5Bsys%5D=tinkoff&payment%5Bsystranid%5D=7695032769&payment%5Borderid%5D=1992217563&payment%5Bproducts%5D%5B0%5D%5Bname%5D=%D0%94%D0%BE%D1%81%D1%82%D1%83%D0%BF+%D0%BA+%D1%82%D0%B5%D1%81%D1%82%D1%83+%D0%BD%D0%B0+1+%D1%87%D0%B5%D0%BB.&payment%5Bproducts%5D%5B0%5D%5Bquantity%5D=1&payment%5Bproducts%5D%5B0%5D%5Bamount%5D=5&payment%5Bproducts%5D%5B0%5D%5Bprice%5D=5&payment%5Bamount%5D=5&formid=form644050497&formname=Cart&API-key=D2swqPZF2Rfj4LrYJle7MoKvvRBRFcAhODGsXPZyzNMLKjdjXYutl5esrZGiNVRoj6dzG2rIHN1CmNKc0GVVqBHn1M348txviq2QzgBpQg7LxSO4vDfXmqbHcQzKfQMT";

                //axios.post("./api/payment_event.php", {Name:"Алексей", telegram: "my_telegram", client_id: "client ID"})                
                axios.post("./queries/get_current_user_name2.php")
                //axios.post("./api/test_call_procedure.php", v_json_param)
                .then(function (response) {
                    console.log(response.data);
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
*/                
            }

            

        },
        template: 
        `
        <div class='sidenav'>
                <div class="form-element">
                    <input class="msll_button" type="button" value = "Новый клиент" @click="onClikCreateNewClient">
                    <label>Фильтр</label>

                    <select class='msll_filter' name='fieldFirstFilter' id='fieldFirstFilter' required @change='onSelectFirstFilter();'>
                        <option disabled value=''>Выберите поле</option>
                        <option v-for="option in options1" :value="option.value">{{ option.text }}</option>
                    </select>

                    <select class='msll_filter' name='search_operation' id='search_operation' required>
                        <option v-for="option in options2" :value="option.value">{{ option.text }}</option>
                    </select>
                    
                    <input class='msll_filter' type="search" list="options" id=item_for_search>
                    <datalist class='msll_filter' id="options">
                        <option v-for="item in items_for_search" :value="item"/>
                    </datalist>

                    <input class="msll_button" type="button" value = "Применить" @click="onClikBtnApply(true)">

                    <label>Применённые фильтры</label>
                    <div>
                        <table class='msll_table'>
                            <tr v-for="condition in conditions">
                                <td>{{condition.object}}</td>
                                <td>{{condition.operation}}</td>
                                <td>{{condition.value}}</td>
                                <!--<td><a href='#'  v-on:click='onClikDeleteItemOfConditions(condition.item_id)'>{{condition.item_id}}</a></td>-->
                                <td><input type="button" value = "Х" @click='onClikDeleteItemOfConditions(condition.item_id)'></td>
                            </tr>
                        </table>
                    </div>

                    <input class="msll_button" type="button" value = "Тест POST" @click="onClikBtnTestPost">
                    <input class="msll_button" type="button" value = "Тест POST 2" @click="onClikBtnTestPost2">
                    <input class="msll_button" type="button" value = "Тест POST 3" @click="onClikBtnTestPost3">

                </div>
        </div>
        <div class='msll_body'>
            <div class='no-copy'>
                <table class='msll_table'>
                    <tbody>
                        <tr>
                            <th width='3%'>№</th>
                            <th width='22%'>ФИО</th>
                            <th width='15%'>Почта</th>
                            <th width='10%'>Телефон</th>
                            <th width='15%'>Telegram</th>
                            <th width='48%'>Комментарий</th>
                            
                        </tr>

                        <tr v-for="client_item in list_of_clients">
                            <td>{{client_item.num}}</td>
                            <td style='position: relative;'><button  class="msll_button_in_table" type="button" @click='onClikClientDetail(client_item.client_id)'> {{client_item.fio}}</button></td>

                            <td>
                                <div v-for="item in client_item.client_emails">
                                    <a :href=createMTLink(item) target="_blank">{{item}}</a>
                                </div>
                            </td>
                            <td>
                                <div v-for="item in client_item.client_phones">
                                    <a :href=createTelLink(item) target="_blank">{{formate_phone(item)}}</a>
                                </div>
                            </td>
                            <td>
                                <div v-for="item in client_item.client_telegrams">
                                    <a :href=createTGLink(item) target="_blank">{{item}}</a>
                                </div>
                            </td>
                            <td>{{client_item.client_comment}}</td>
                        </tr>
                    </tbody>                    
                </table>
                <br/><br/>
            </div>
            <div id="form_Detail_Info_Of_Client" class="modal">
                <Detail-Info-Of-Client ref="childRef" @update_client_data="onChangeClientData"/>
            </div>            

            <div id="form_Create_New_Client" class="modal">
                <Form-Create-New-Client ref="FormCreateNewClientRef" @client_created="onClientCreted"/>
            </div>     

        </div>  
        `        
}
