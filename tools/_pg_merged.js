export default {
    data() {
        return {
            current_route_name: "documents",
            templates: [],
            available_categories: [],
            selected_template: null,
            template_fields: [],
            template_field_map: {},
            filter_query: "",
            filter_category: "",
            form_data: {},
            is_loading_templates: false,
            is_loading_template_config: false,
            is_resolving_company: false,
            is_resolving_bank: false,
            is_generating_document: false,
            status_message: "",
            status_type: "",
            generated_download_url: "",
            generated_file_name: "",
            // БИК подставлен из БД по связке шаблон + ИНН (требование п. 8)
            bik_loaded_from_db: false,
            inn_for_loaded_bik: "",
            is_loading_stored_bik: false
        }
    },
    computed: {
        filtered_templates() {
            const query = (this.filter_query || "").trim().toLowerCase();
            const category = (this.filter_category || "").trim().toLowerCase();

            return (this.templates || []).filter((template_item) => {
                const template_name = (template_item.template_name || "").toLowerCase();
                const template_description = (template_item.template_description || "").toLowerCase();
                const template_category = (template_item.template_category || "").toLowerCase();
                const template_tags = Array.isArray(template_item.filter_tags)
                    ? template_item.filter_tags.join(" ").toLowerCase()
                    : "";

                const matches_query = query.length === 0
                    || template_name.includes(query)
                    || template_description.includes(query)
                    || template_tags.includes(query);

                const matches_category = category.length === 0 || template_category === category;

                return matches_query && matches_category;
            });
        }
    },
    mounted() {
        this.$root.check_for_permition_route(this.current_route_name);
        const navigationMenuRef = typeof this.$root.getNavigationMenuRef === 'function'
            ? this.$root.getNavigationMenuRef()
            : this.$root.$refs.ref_NavigationMenu;
        if (navigationMenuRef && typeof navigationMenuRef.setActivMenuItem === 'function') {
            navigationMenuRef.setActivMenuItem(this.current_route_name);
        }
        this.loadTemplates();
    },
    beforeUnmount() {
        this.releaseGeneratedUrl();
    },
    methods: {
        showMessage(in_message, in_type = "info") {
            this.status_message = in_message || "";
            this.status_type = in_type || "info";
            if (this.$root && this.$root.$refs && this.$root.$refs.ref_FormModalMessage && in_type === "error") {
                this.$root.$refs.ref_FormModalMessage.init(this, in_message);
                document.getElementById("id_FormModalMessage").style.display = "block";
            }
        },

        clearMessage() {
            this.status_message = "";
            this.status_type = "";
        },

        releaseGeneratedUrl() {
            if (this.generated_download_url) {
                URL.revokeObjectURL(this.generated_download_url);
            }
            this.generated_download_url = "";
            this.generated_file_name = "";
        },

        setSpinnerVisible(isVisible) {
            const spinner = document.getElementById("id_spinner_panel");
            if (!spinner) {
                return;
            }
            spinner.style.display = isVisible ? "block" : "none";
        },

        normalizeFieldValue(in_value) {
            if (in_value === undefined || in_value === null) {
                return "";
            }
            if (typeof in_value === "object") {
                return "";
            }
            return String(in_value);
        },

        getFieldInputType(in_field) {
            const field_type = (in_field && in_field.field_type ? in_field.field_type : "text").toLowerCase();
            if (["email", "tel", "number", "date"].includes(field_type)) {
                return field_type;
            }
            return "text";
        },

        isTextareaField(in_field) {
            return Boolean(in_field && String(in_field.field_type || "").toLowerCase() === "textarea");
        },

        getFieldValue(in_field_code) {
            return this.normalizeFieldValue(this.form_data[in_field_code]);
        },

        setFieldValue(in_field_code, in_value) {
            this.form_data[in_field_code] = this.normalizeFieldValue(in_value);
        },

        applyDataSource(in_data) {
            if (!in_data || typeof in_data !== "object") {
                return;
            }

            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                const source_field_code = field_item.source_field_code || field_code;
                if (!field_code || !Object.prototype.hasOwnProperty.call(in_data, source_field_code)) {
                    continue;
                }
                const next_value = this.normalizeFieldValue(in_data[source_field_code]);
                if (next_value !== "") {
                    this.setFieldValue(field_code, next_value);
                }
            }
        },

        /** Текущая дата для поля contract_date (п. 14 требований). */
        getTodayIsoDate() {
            const today = new Date();
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const day = String(today.getDate()).padStart(2, "0");
            return `${today.getFullYear()}-${month}-${day}`;
        },

        initializeFormDataFromTemplate() {
            const next_form_data = {};
            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                if (!field_code) {
                    continue;
                }
                let default_value = this.normalizeFieldValue(field_item.default_value || "");
                // при открытии шаблона подставляем сегодня; пользователь может изменить
                if (field_code === "contract_date" && default_value === "") {
                    default_value = this.getTodayIsoDate();
                }
                next_form_data[field_code] = default_value;
            }

            if (!Object.prototype.hasOwnProperty.call(next_form_data, "inn")) {
                next_form_data.inn = "";
            }
            if (!Object.prototype.hasOwnProperty.call(next_form_data, "bik")) {
                next_form_data.bik = "";
            }

            this.form_data = next_form_data;
            this.releaseGeneratedUrl();
            this.clearMessage();
        },

        async loadTemplates() {
            this.is_loading_templates = true;
            this.clearMessage();
            try {
                const response = await axios.get("./queries/get_document_templates.php");
                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось загрузить шаблоны");
                }

                this.templates = response.data.templates || [];
                this.available_categories = response.data.available_categories || [];

                if (this.templates.length > 0) {
                    await this.selectTemplate(this.templates[0]);
                } else {
                    this.selected_template = null;
                    this.template_fields = [];
                    this.template_field_map = {};
                    this.form_data = {};
                }
            } catch (error) {
                this.showMessage("Не удалось загрузить список шаблонов.<br>" + String(error), "error");
            } finally {
                this.is_loading_templates = false;
            }
        },

        async selectTemplate(in_template) {
            if (!in_template || !in_template.template_id) {
                return;
            }

            this.is_loading_template_config = true;
            this.releaseGeneratedUrl();
            this.clearMessage();
            try {
                const response = await axios.get("./queries/get_document_template_config.php", {
                    params: {
                        template_id: in_template.template_id
                    }
                });

                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось загрузить конфигурацию шаблона");
                }

                this.selected_template = response.data.template || in_template;
                this.template_fields = response.data.fields || [];
                this.template_field_map = (response.data.template && response.data.template.field_map) ? response.data.template.field_map : {};
                this.initializeFormDataFromTemplate();

                const inn_after_init = this.digitsOnly(this.getFieldValue("inn"));
                if (this.isValidInn(inn_after_init)) {
                    await this.loadStoredBikForInn(inn_after_init);
                }
            } catch (error) {
                this.showMessage("Не удалось загрузить конфигурацию выбранного шаблона.<br>" + String(error), "error");
            } finally {
                this.is_loading_template_config = false;
            }
        },

        async resolveCompanyByInn() {
            const inn = (this.getFieldValue("inn") || "").replace(/\D+/g, "");
            if (!/^\d{10,12}$/.test(inn)) {
                this.showMessage("Введите корректный ИНН организации.", "error");
                return;
            }

            this.is_resolving_company = true;
            this.setSpinnerVisible(true);
            try {
                const response = await axios.get("./queries/resolve_company_by_inn.php", {
                    params: {
                        inn: inn
                    }
                });

                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось получить данные организации");
                }

                this.applyDataSource(response.data.company || {});
                this.setFieldValue("inn", inn);
                if (response.data.stored_account) {
                    this.setFieldValue("checking_account", response.data.stored_account);
                }
                this.showMessage("Реквизиты организации обновлены по DaData.", "success");
            } catch (error) {
                this.showMessage("Ошибка при получении данных организации.<br>" + String(error), "error");
            } finally {
                this.is_resolving_company = false;
                this.setSpinnerVisible(false);
            }
        },

        async resolveBankByBik() {
            const bik = (this.getFieldValue("bik") || "").replace(/\D+/g, "");
            if (!/^\d{9}$/.test(bik)) {
                this.showMessage("Введите корректный БИК банка.", "error");
                return;
            }

            this.is_resolving_bank = true;
            this.setSpinnerVisible(true);
            try {
                const response = await axios.get("./queries/resolve_bank_by_bik.php", {
                    params: {
                        bik: bik
                    }
                });

                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось получить данные банка");
                }

                this.applyDataSource(response.data.bank || {});
                this.setFieldValue("bik", bik);
                this.showMessage("Банковские реквизиты обновлены по DaData Bank.", "success");
            } catch (error) {
                this.showMessage("Ошибка при получении данных банка.<br>" + String(error), "error");
            } finally {
                this.is_resolving_bank = false;
                this.setSpinnerVisible(false);
            }
        },

        async saveCounterpartyAccountSilently() {
            const inn = (this.getFieldValue("inn") || "").replace(/\D+/g, "");
            const checking_account = (this.getFieldValue("checking_account") || "").replace(/\D+/g, "");

            if (!/^\d{10,12}$/.test(inn) || !/^\d{20}$/.test(checking_account)) {
                return;
            }

            try {
                await axios.post("./queries/save_counterparty_account.php", {
                    inn: inn,
                    checking_account: checking_account
                });
            } catch (error) {
                console.log("Не удалось сохранить расчётный счёт:", error);
            }
        },

        getDownloadFileName(in_payload) {
            if (in_payload && in_payload.download_filename) {
                return in_payload.download_filename;
            }
            if (this.selected_template && this.selected_template.template_code) {
                return this.selected_template.template_code + ".docx";
            }
            return "document.docx";
        },

        base64ToUint8Array(in_base64) {
            const normalized = in_base64 || "";
            const binaryString = atob(normalized);
            const bytes = new Uint8Array(binaryString.length);
            for (let index = 0; index < binaryString.length; index += 1) {
                bytes[index] = binaryString.charCodeAt(index);
            }
            return bytes;
        },

        createDocxBlobUrl(in_payload) {
            const base64 = in_payload && in_payload.document_docx_base64
                ? in_payload.document_docx_base64
                : "";
            if (!base64) {
                throw new Error("Сервер не вернул сформированный DOCX");
            }

            const content_type = in_payload && in_payload.content_type
                ? in_payload.content_type
                : "application/vnd.openxmlformats-officedocument.wordprocessingml.document";

            const bytes = this.base64ToUint8Array(base64);
            return URL.createObjectURL(new Blob([bytes], { type: content_type }));
        },

        triggerBrowserDownload(in_url, in_filename) {
            const link = document.createElement("a");
            link.href = in_url;
            link.download = in_filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        async generateDocument() {
            if (!this.selected_template || !this.selected_template.template_id) {
                this.showMessage("Сначала выберите шаблон документа.", "error");
                return;
            }

            this.is_generating_document = true;
            this.setSpinnerVisible(true);
            this.releaseGeneratedUrl();

            try {
                const response = await axios.post("./queries/generate_document.php", {
                    template_id: this.selected_template.template_id,
                    form_data: this.form_data
                });

                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось подготовить документ");
                }

                await this.saveCounterpartyAccountSilently();

                const blobUrl = this.createDocxBlobUrl(response.data);
                const fileName = this.getDownloadFileName(response.data);
                this.generated_download_url = blobUrl;
                this.generated_file_name = fileName;
                this.triggerBrowserDownload(blobUrl, fileName);
                this.showMessage("Документ успешно сформирован и готов к скачиванию.", "success");
            } catch (error) {
                this.showMessage("Ошибка при генерации документа.<br>" + String(error), "error");
            } finally {
                this.is_generating_document = false;
                this.setSpinnerVisible(false);
            }
        }
    },
    template:
    `
    <div class="sidenav documents_sidenav">
        <div class="documents_panel">
            <div class="documents_panel_title">Шаблоны документов</div>

            <label class="label-align-left documents_label" for="documents_filter_query">Поиск</label>
            <input
                id="documents_filter_query"
                class="msll_filter documents_filter_input"
                type="text"
                v-model="filter_query"
                placeholder="Введите название или тег"
            >

            <label class="label-align-left documents_label" for="documents_filter_category">Категория</label>
            <select id="documents_filter_category" class="msll_filter documents_filter_input" v-model="filter_category">
                <option value="">Все категории</option>
                <option v-for="category_item in available_categories" :value="category_item">{{category_item}}</option>
            </select>

            <div class="documents_template_list">
                <div
                    v-for="template_item in filtered_templates"
                    :key="template_item.template_id"
                    class="documents_template_card"
                    :class="{ 'documents_template_card--active': selected_template && selected_template.template_id === template_item.template_id }"
                    @click="selectTemplate(template_item)"
                >
                    <div class="documents_template_name">{{template_item.template_name}}</div>
                    <div class="documents_template_category" v-if="template_item.template_category">{{template_item.template_category}}</div>
                    <div class="documents_template_description" v-if="template_item.template_description">{{template_item.template_description}}</div>
                </div>

                <div v-if="!is_loading_templates && filtered_templates.length === 0" class="documents_empty_state">
                    По текущим фильтрам шаблоны не найдены.
                </div>
            </div>
        </div>
    </div>

    <div class="msll_body documents_body">
        <div class="documents_content_card" v-if="selected_template">
            <div class="documents_header_row">
                <div>
                    <h2 class="documents_title">{{selected_template.template_name}}</h2>
                    <div class="documents_subtitle" v-if="selected_template.template_description">{{selected_template.template_description}}</div>
                </div>
                <div class="documents_chip" v-if="selected_template.template_category">{{selected_template.template_category}}</div>
            </div>

            <div class="documents_status" v-if="status_message" :class="'documents_status--' + (status_type || 'info')">
                {{status_message}}
            </div>

            <div class="documents_actions">
                <input class="msll_middle_button" type="button" value="Подтянуть по ИНН" @click="resolveCompanyByInn()" :disabled="is_resolving_company || is_generating_document || is_loading_template_config">
                <input class="msll_middle_button" type="button" value="Подтянуть по БИК" @click="resolveBankByBik()" :disabled="is_resolving_bank || is_generating_document || is_loading_template_config">
                <input class="msll_middle_button" type="button" value="Сформировать PDF" @click="generateDocument()" :disabled="is_generating_document || is_loading_template_config || !selected_template">
            </div>

            <div class="documents_form_grid">
                <div class="documents_form_field" v-for="field_item in template_fields" :key="field_item.field_id || field_item.field_code">
                    <label class="label-align-left documents_label">{{field_item.field_label}}</label>

                    <textarea
                        v-if="isTextareaField(field_item)"
                        class="documents_textarea"
                        :placeholder="field_item.placeholder || ''"
                        :value="getFieldValue(field_item.field_code)"
                        @input="setFieldValue(field_item.field_code, $event.target.value)"
                    ></textarea>

                    <input
                        v-else
                        class="documents_field_input"
                        :type="getFieldInputType(field_item)"
                        :placeholder="field_item.placeholder || ''"
                        :value="getFieldValue(field_item.field_code)"
                        @input="setFieldValue(field_item.field_code, $event.target.value)"
                    >

                    <div class="documents_field_hint" v-if="field_item.is_required">Обязательное поле</div>
                </div>
            </div>

            <div class="documents_result_box" v-if="generated_download_url">
                <div class="documents_result_title">Последний сформированный файл</div>
                <a class="documents_result_link" :href="generated_download_url" :download="generated_file_name">{{generated_file_name}}</a>
            </div>
        </div>

        <div class="documents_content_card" v-else>
            <div class="documents_empty_state">Выберите шаблон документа из списка слева.</div>
        </div>
    </div>
    `
}