export default {
    emits: ["update_client_data"],
    data() {
        return {
                    detail_client_products: "",
                    detail_client_products_saved: "",
                    detail_client_id: "",
                    WarningMessage: "",
                    spr_products: "",
                    spr_sales_status: "",
                    test_date: ""
        }
    },
    methods: {
                // When the user clicks on <span> (x), close the modal
                onClickCloseFormEditorProductsOfClient(){
                    document.getElementById("Form_Editor_Products_Of_Client").style.display = "none";
                },
                async activate(clientID){
                    this.WarningMessage = "";
                    // Наполнение справочников
                    // справочник пролдуктов
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_all_products.php');
                        if (response.data) {
                            //console.log(response.data);
                            this.spr_products = response.data; 
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

                    // справочник статсусов sale
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_spr_sales_status.php');
                        if (response.data) {
                            //console.log(response.data);
                            this.spr_sales_status = response.data; 
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
                    

                    //Получаем актуальные адреса product
                    try {
                        this.detail_client_id=clientID;
                        
                        const response = await axios.get('./queries/get_sold_products_by_id.php?clientID=' + clientID);
                        if (response.data) {
                            //console.log(response.data);
                            this.detail_client_products = response.data; 
                            this.detail_client_products_saved = JSON.parse(JSON.stringify(response.data));
                            
                            //сделать элемент модальным     
                            document.getElementById("Form_Editor_Products_Of_Client").style.display = "block";

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
                onClickApplyFormEditorProductsOfClient(){
                    
                    let var_client_products_saved = this.detail_client_products_saved;
                    let var_client_products = this.detail_client_products;
                    let var_client_id = this.detail_client_id;

                    //добавляем или обнолвяем продукты
                    var_client_products.forEach(function(item) { 
                        
                        //проверяем не новый ли это продукт
                        if (var_client_products_saved.findIndex((item_saved) => item_saved.sale_id === item.sale_id) == -1){
                            //alert("Это новый адрес id: " + item.sale_id + " product: " + item.product_name);

                            let is_resp_success = false;                                        
                            if (item.product_name == ""){item.product_name = "НЕТ ПРОДУКТА"}
                            if (item.status == ""){item.status = "НЕ ОПРЕДЕЛЁН"}

                            is_resp_success= axios.post("./queries/add_client_product.php", {client_id: var_client_id, product_name: item.product_name, date: item.usdate, status: item.status, comment: item.comment})
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
                            

                        } else if ((item.usdate != var_client_products_saved[var_client_products_saved.findIndex((item_saved) => item_saved.sale_id === item.sale_id)].usdate)
                                 ||(item.product_name != var_client_products_saved[var_client_products_saved.findIndex((item_saved) => item_saved.sale_id === item.sale_id)].product_name)
                                 ||(item.status != var_client_products_saved[var_client_products_saved.findIndex((item_saved) => item_saved.sale_id === item.sale_id)].status)
                                 ||(item.comment != var_client_products_saved[var_client_products_saved.findIndex((item_saved) => item_saved.sale_id === item.sale_id)].comment)
                            
                            ){ 
                            //значения product текущий и сохранённый не равны
                            //alert("надо обновить адрес id: " + item.sale_id + " product: " + item.product_name);
                            
                            let is_resp_success = false;                                        
                            is_resp_success = axios.post("./queries/update_client_product_by_id.php", {sale_id: item.sale_id, usdate: item.usdate, product_name: item.product_name, status: item.status, comment: item.comment})
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
                    var_client_products_saved.forEach(function(item_saved) {
                        if (var_client_products.findIndex((item) => item.sale_id === item_saved.sale_id) == -1){
                            //alert("удалить адрес id: " + item_saved.sale_id + " product: " + item_saved.product_name);
                            let is_resp_success = false;                                        
                            is_resp_success= axios.post("./queries/delete_client_product_by_id.php", {sale_id: item_saved.sale_id})
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
                    
                                       
                    //alert( this.detail_client_products[1].product );
                    
                    this.$emit('update_client_data', 'Products');
                    this.onClickCloseFormEditorProductsOfClient();

                    
                },
                onClikDeleteDeleteProduct(in_sale_id){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    this.detail_client_products.splice(this.detail_client_products.findIndex((item) => item.sale_id === in_sale_id), 1); 
                },
                onClickAddProduct(){
                    this.WarningMessage = "ЕСТЬ НЕСОХРАНЁННЫЕ ИЗМЕНЕНИЯ";
                    let date = new Date();
                    let v_usdate = date.toISOString().slice(0, 10);
                    //console.log(v_usdate);
                    this.detail_client_products.push({ sale_id: Date.now(), usdate: v_usdate, product_name: "", status: "НЕ ОПРЕДЕЛЁН", comment: "", is_disable: false}); 
                }
                
    },
    template: 
    `
    <!-- Modal content -->
    <div class="modal-content-80">
        <div class="modal-header">
            <span class="close" @click="onClickCloseFormEditorProductsOfClient()">&times;</span>
            <h2>Изменение списка продуктов клиента</h2>
        </div>
        <div class="modal-body">
            <div class="ERROR">{{WarningMessage}}<br/></div>
            <table class='msll_table'>
                <tbody>
                    <tr>
                        <th width='10%'>Дата покупки</th>
                        <th width='50%'>Название продукта</th>
                        <th width='10%'>Статус</th>
                        <th width='29%'>Комментарий</th>
                        <th width='1%'>Х</th>
                    </tr>
                    <tr v-for="item in detail_client_products">
                        <td><input class="msll_filter" type="date" v-model="item.usdate"/></td>
                        <td>
                            <input class="msll_filter" type="input" list="datalist_spr_prodicts" v-model="item.product_name" :disabled="item.is_disable" />
                            <datalist class="msll_filter" id="datalist_spr_prodicts">
                                <option v-for="spr_item in spr_products" :value="spr_item.product_name"/>
                            </datalist>
                        </td>
                        <td>
                            <select class="msll_filter" v-model="item.status">
                                <option v-for="spr_item in spr_sales_status" :value="spr_item.status_name" :key="spr_item.status_name">{{ spr_item.status_name }}</option>
                            </select>                        
<!--
                            <input class="msll_filter" type="input" list="datalist_spr_sales_status" v-model="item.status"/>
                            <datalist class="msll_filter" id="datalist_spr_sales_status">
                                <option v-for="spr_item in spr_sales_status" :value="spr_item.status_name"/>
                            </datalist>
-->                            
                        </td>
                        <td><input class="msll_filter" type="input" v-model="item.comment"/></td>
                        <td><input type="button" value = "&times;" @click='onClikDeleteDeleteProduct(item.sale_id)'></td>
                    </tr>
                </tbody>
            </table>
            <button class="msll_middle_button" type="button" @click="onClickAddProduct()">Добавить продукт</button>
            <button class="msll_middle_button" type="button" @click="onClickApplyFormEditorProductsOfClient()">Применить</button>
            <button class="msll_middle_button" type="button" @click="onClickCloseFormEditorProductsOfClient()">Отменить</button>

        </div>
        <div class="modal-footer">
        </div>
    </div>      
    `
    }
