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
            field_errors: {},
            is_loading_templates: false,
            is_loading_template_config: false,
            is_autofilling_data: false,
            is_generating_document: false,
            status_message: "",
            status_type: "",
            generated_download_url: "",
            generated_file_name: "",
            // UI п. 7–8: модальное редактирование полей шаблона
            is_edit_modal_open: false,
            edit_modal_form_snapshot: {},
            // UI п. 6: визуальный предпросмотр DOCX (docx-preview, как в Word)
            has_document_preview: false,
            is_loading_preview: false,
            /** Масштаб документа в области предпросмотра (1 = 100%; 1.6 ≈ было ×2, уменьшено на 20%). */
            preview_document_scale: 1.6,
            // Варианты восстановления из document_field_cache (требования п. 21–25)
            cache_field_options: {},
            fields_loaded_from_cache: {},
            inn_for_loaded_cache: "",
            is_loading_document_cache: false,
            // Реестр оформленных договоров (п. 10–12)
            issued_registry_rows: [],
            issued_registry_filter: "",
            is_loading_registry: false,
            is_saving_registry_row_id: null,
            // Режим редактирования одной строки реестра (ключ строки или null)
            issued_registry_editing_row_key: null,
            // Снимок строки на момент входа в редактирование (для «Отменить»)
            issued_registry_edit_snapshot: null,
            table_blocks_config: {},
            // п. 13–15: по умолчанию реестр на всё рабочее пространство; шаблоны — по кнопке
            is_templates_view_active: false,
            // UI: выдвижная панель шаблонов — клик по шильдику
            is_documents_sidenav_expanded: false
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
        },

        isDocumentsSidenavOpen() {
            return Boolean(this.is_documents_sidenav_expanded);
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
        // Список шаблонов подгружаем заранее, конфигурацию — только при входе в режим шаблонов
        this.loadTemplates(false);
        this.loadIssuedRegistry();
    },
    beforeUnmount() {
        this.releaseGeneratedUrl();
    },
    methods: {
        showMessage(in_message, in_type = "info") {
            const raw_message = in_message || "";
            this.status_message = String(raw_message).replace(/<br\s*\/?>/gi, " ");
            this.status_type = in_type || "info";
            if (this.$root && this.$root.$refs && this.$root.$refs.ref_FormModalMessage && in_type === "error") {
                this.$root.$refs.ref_FormModalMessage.init(this, String(raw_message));
                const form_modal = document.getElementById("id_FormModalMessage");
                if (form_modal) {
                    // Поверх модалки редактирования (documents_edit_modal, z-index 2000)
                    form_modal.style.zIndex = "3000";
                    form_modal.style.display = "block";
                }
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
            if (Array.isArray(in_value)) {
                return in_value;
            }
            if (typeof in_value === "object") {
                return "";
            }
            return String(in_value);
        },

        digitsOnly(in_value) {
            return this.normalizeFieldValue(in_value).replace(/\D+/g, "");
        },

        isValidInn(in_value) {
            return /^\d{10,12}$/.test(this.digitsOnly(in_value));
        },

        isValidBik(in_value) {
            return /^\d{9}$/.test(this.digitsOnly(in_value));
        },

        isValidOptionalEmail(in_value) {
            const value = this.normalizeFieldValue(in_value).trim();
            if (value.length === 0) {
                return true;
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        },

        validateAccountChecksum(seed, account) {
            if (!/^\d{3}$/.test(seed) || !/^\d{20}$/.test(account)) {
                return false;
            }

            const controlString = seed + account;
            const coefficients = [7, 1, 3];
            let sum = 0;
            for (let index = 0; index < controlString.length; index += 1) {
                sum += parseInt(controlString[index], 10) * coefficients[index % 3];
            }
            return sum % 10 === 0;
        },

        isValidCheckingAccount(in_account, in_bik) {
            const account = this.digitsOnly(in_account);
            const bik = this.digitsOnly(in_bik);
            if (!/^\d{20}$/.test(account) || !/^\d{9}$/.test(bik)) {
                return false;
            }
            return this.validateAccountChecksum(bik.slice(-3), account);
        },

        isValidCorrAccount(in_account, in_bik) {
            const account = this.digitsOnly(in_account);
            const bik = this.digitsOnly(in_bik);
            if (!/^\d{20}$/.test(account) || !/^\d{9}$/.test(bik)) {
                return false;
            }
            return this.validateAccountChecksum("0" + bik.slice(4, 6), account);
        },

        setFieldErrors(in_errors) {
            this.field_errors = { ...(in_errors || {}) };
        },

        clearFieldErrors() {
            this.field_errors = {};
        },

        clearFieldError(in_field_code) {
            if (!Object.prototype.hasOwnProperty.call(this.field_errors, in_field_code)) {
                return;
            }

            const next_errors = { ...this.field_errors };
            delete next_errors[in_field_code];
            this.field_errors = next_errors;
        },

        isFieldInvalid(in_field_code) {
            return Boolean(this.field_errors && this.field_errors[in_field_code]);
        },

        /** Подсветка ошибки на поле «конечного выбора» (п. 9 UI), не на выпадающем списке кэша. */
        isFieldInvalidOnInput(in_field_code) {
            return this.isFieldInvalid(in_field_code);
        },

        isCacheFieldSelectInvalid(in_field_code) {
            return false;
        },

        getFieldError(in_field_code) {
            return this.field_errors && this.field_errors[in_field_code]
                ? this.field_errors[in_field_code]
                : "";
        },

        getFieldMeta(in_field_code) {
            return (this.template_fields || []).find((field_item) => field_item.field_code === in_field_code) || null;
        },

        getFieldLabel(in_field_code) {
            const field_meta = this.getFieldMeta(in_field_code);
            if (field_meta && field_meta.field_label) {
                return field_meta.field_label;
            }
            return in_field_code;
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

        isTableField(in_field) {
            return Boolean(in_field && String(in_field.field_type || "").toLowerCase() === "table");
        },

        isComputedNumberField(in_field) {
            return Boolean(in_field && String(in_field.data_source || "").toLowerCase() === "computed_number");
        },

        /** Роль реестра: из конфига шаблона или по template_code (если SQL миграции ролей не применяли). */
        getResolvedRegistryRole() {
            const template = this.selected_template || {};
            let role = String(template.registry_role || "").trim().toLowerCase();
            if (role && role !== "none") {
                return role;
            }
            const template_code = String(template.template_code || "").trim();
            if (["legal_services_agreement_demo", "legal_services_agreement_linki"].includes(template_code)) {
                return "contract";
            }
            return "none";
        },

        getTableBlockColumns(in_field_code) {
            const config = this.table_blocks_config[in_field_code];
            if (config && Array.isArray(config.columns)) {
                return config.columns;
            }
            return ["service_name", "service_price"];
        },

        getTableFieldRows(in_field_code) {
            const raw = this.form_data[in_field_code];
            return Array.isArray(raw) ? raw : [];
        },

        ensureTableFieldInitialized(in_field_code) {
            if (!Array.isArray(this.form_data[in_field_code])) {
                this.form_data[in_field_code] = [];
            }
        },

        addTableFieldRow(in_field_code) {
            this.ensureTableFieldInitialized(in_field_code);
            const columns = this.getTableBlockColumns(in_field_code);
            const empty_row = {};
            for (const column_code of columns) {
                empty_row[column_code] = "";
            }
            this.form_data[in_field_code] = [...this.getTableFieldRows(in_field_code), empty_row];
        },

        removeTableFieldRow(in_field_code, in_row_index) {
            const rows = this.getTableFieldRows(in_field_code).filter((_, index) => index !== in_row_index);
            this.form_data[in_field_code] = rows;
        },

        setTableFieldCell(in_field_code, in_row_index, in_column_code, in_value) {
            this.ensureTableFieldInitialized(in_field_code);
            const rows = this.getTableFieldRows(in_field_code).map((row_item, index) => {
                if (index !== in_row_index) {
                    return { ...row_item };
                }
                return {
                    ...row_item,
                    [in_column_code]: this.normalizeFieldValue(in_value)
                };
            });
            this.form_data[in_field_code] = rows;
        },

        getFieldValue(in_field_code) {
            const raw = this.form_data[in_field_code];
            if (Array.isArray(raw)) {
                return raw;
            }
            return this.normalizeFieldValue(raw);
        },

        setFieldValue(in_field_code, in_value, in_options = {}) {
            if (Array.isArray(in_value)) {
                this.form_data[in_field_code] = in_value;
            } else {
                this.form_data[in_field_code] = this.normalizeFieldValue(in_value);
            }
            this.clearFieldError(in_field_code);

            // ручное изменение кэшируемого поля сбрасывает признак автоподстановки
            if (!in_options.fromStoredLoad && this.isCacheableField(in_field_code)) {
                this.fields_loaded_from_cache[in_field_code] = false;
            }
        },

        getTemplateCacheFields() {
            return Array.isArray(this.selected_template && this.selected_template.cache_fields)
                ? this.selected_template.cache_fields
                : [];
        },

        getTemplateCacheKeyField() {
            const key_field = this.selected_template && this.selected_template.cache_key_field
                ? String(this.selected_template.cache_key_field).trim()
                : "inn";
            return key_field || "inn";
        },

        getCacheKeyValue() {
            const key_field = this.getTemplateCacheKeyField();
            if (key_field === "inn") {
                return this.digitsOnly(this.getFieldValue(key_field));
            }
            return this.normalizeFieldValue(this.getFieldValue(key_field)).trim();
        },

        isCacheableField(in_field_code) {
            return this.getTemplateCacheFields().includes(in_field_code);
        },

        /** Поле пустое — в него можно подставить значение из кэша (требование п. 26). */
        isCacheableFieldEmpty(in_field_code) {
            return this.normalizeFieldValue(this.getFieldValue(in_field_code)).trim() === "";
        },

        resetCacheAutoloadState() {
            this.cache_field_options = {};
            this.fields_loaded_from_cache = {};
            this.inn_for_loaded_cache = "";
        },

        hasMultipleCacheOptions(in_field_code) {
            const bucket = this.cache_field_options[in_field_code];
            return Boolean(bucket && Array.isArray(bucket.options) && bucket.options.length > 1);
        },

        getCacheFieldOptions(in_field_code) {
            const bucket = this.cache_field_options[in_field_code];
            return bucket && Array.isArray(bucket.options) ? bucket.options : [];
        },

        /** Дата сохранения в кэше для подписи в select: DD.MM.YYYY HH:MM:SS */
        formatCacheOptionSavedAt(in_saved_at) {
            const raw = this.normalizeFieldValue(in_saved_at).trim();
            if (raw === "") {
                return "";
            }

            const mysql_match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
            if (mysql_match) {
                return `${mysql_match[3]}.${mysql_match[2]}.${mysql_match[1]} ${mysql_match[4]}:${mysql_match[5]}:${mysql_match[6]}`;
            }

            const localized_match = raw.match(/^(\d{2})\.(\d{2})\.(\d{4}) (\d{2}):(\d{2}):(\d{2})$/);
            if (localized_match) {
                return raw;
            }

            const parsed = new Date(raw);
            if (!Number.isNaN(parsed.getTime())) {
                const pad = (part) => String(part).padStart(2, "0");
                return `${pad(parsed.getDate())}.${pad(parsed.getMonth() + 1)}.${parsed.getFullYear()} ${pad(parsed.getHours())}:${pad(parsed.getMinutes())}:${pad(parsed.getSeconds())}`;
            }

            return raw;
        },

        getCacheOptionLabel(in_option_item) {
            if (!in_option_item) {
                return "";
            }
            const value = this.normalizeFieldValue(in_option_item.value);
            const saved_at_label = this.formatCacheOptionSavedAt(in_option_item.saved_at);
            return saved_at_label ? `${value} (${saved_at_label})` : value;
        },

        onCacheFieldSelect(in_field_code, in_value) {
            this.setFieldValue(in_field_code, in_value, { fromStoredLoad: true });
            this.fields_loaded_from_cache[in_field_code] = true;
        },

        applyCachedFieldsFromResponse(in_fields) {
            if (!in_fields || typeof in_fields !== "object") {
                return false;
            }

            let applied_any = false;
            this.cache_field_options = in_fields;

            for (const field_code of this.getTemplateCacheFields()) {
                const bucket = in_fields[field_code];
                if (!bucket) {
                    continue;
                }

                const options = Array.isArray(bucket.options) ? bucket.options : [];
                if (options.length === 0) {
                    continue;
                }

                const latest_value = bucket.latest || options[0].value || "";
                // п. 28: по умолчанию — последнее по дате (latest с сервера); только в пустое поле (п. 26)
                if (latest_value !== "" && this.isCacheableFieldEmpty(field_code)) {
                    this.setFieldValue(field_code, latest_value, { fromStoredLoad: true });
                    this.fields_loaded_from_cache[field_code] = true;
                    applied_any = true;
                }
            }

            return applied_any;
        },

        onInnFieldInput(in_value) {
            const inn_digits = this.digitsOnly(in_value);
            if (this.inn_for_loaded_cache && inn_digits !== this.inn_for_loaded_cache) {
                for (const field_code of this.getTemplateCacheFields()) {
                    this.setFieldValue(field_code, "");
                }
                this.resetCacheAutoloadState();
            }
            this.setFieldValue("inn", in_value);
        },

        async onInnFieldBlur() {
            const cache_key_value = this.getCacheKeyValue();
            if (this.getTemplateCacheKeyField() === "inn" && !this.isValidInn(cache_key_value)) {
                return;
            }
            await this.loadStoredCacheForKey(cache_key_value);
        },

        /**
         * Восстановление кэшируемых полей по ключу шаблона (без привязки кэша к template_id).
         * @returns {Promise<boolean>}
         */
        async loadStoredCacheForKey(in_cache_key_value) {
            if (!this.selected_template || !this.selected_template.template_id) {
                return false;
            }

            const cache_key_field = this.getTemplateCacheKeyField();
            let cache_key_value = in_cache_key_value;
            if (cache_key_field === "inn") {
                cache_key_value = this.digitsOnly(cache_key_value);
                if (!this.isValidInn(cache_key_value)) {
                    return false;
                }
            } else if (this.normalizeFieldValue(cache_key_value).trim() === "") {
                return false;
            }

            if (this.getTemplateCacheFields().length === 0) {
                return false;
            }

            this.is_loading_document_cache = true;
            try {
                const response = await axios.get("./queries/resolve_document_cache.php", {
                    params: {
                        template_id: this.selected_template.template_id,
                        cache_key_value: cache_key_value
                    }
                });

                if (!response.data || response.data.status !== "ok") {
                    return false;
                }

                const applied = this.applyCachedFieldsFromResponse(response.data.fields || {});
                if (applied) {
                    this.inn_for_loaded_cache = cache_key_field === "inn" ? cache_key_value : this.getCacheKeyValue();
                }
                return applied;
            } catch (error) {
                return false;
            } finally {
                this.is_loading_document_cache = false;
            }
        },

        getCacheFieldPlaceholder(in_field) {
            const field_code = in_field && in_field.field_code ? in_field.field_code : "";
            if (this.fields_loaded_from_cache[field_code]) {
                return "Подставлено из сохранённых данных";
            }
            return (in_field && in_field.placeholder) ? in_field.placeholder : "";
        },

        getCacheFieldHint(in_field_code) {
            if (this.hasMultipleCacheOptions(in_field_code)) {
                return "Найдено несколько сохранённых значений — выберите нужное в списке или введите вручную.";
            }
            if (this.fields_loaded_from_cache[in_field_code]) {
                return "Значение подставлено из сохранённых данных. При необходимости можно изменить.";
            }
            if (in_field_code === "bik") {
                return "Укажите БИК или дозаполните форму — значение подставится из кэша, если оно уже сохранялось для этого ИНН.";
            }
            return "Значение можно подставить из кэша после ввода ключа (ИНН) и дозаполнения.";
        },

        isInnField(in_field_code) {
            return in_field_code === "inn";
        },

        isBikField(in_field_code) {
            return in_field_code === "bik";
        },

        applyDataSource(in_data) {
            if (!in_data || typeof in_data !== "object") {
                return;
            }

            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                // ИНН всегда вводится пользователем вручную (требование п. 8)
                if (field_code === "inn") {
                    continue;
                }
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

        collectFieldValidationErrors(in_options = {}) {
            const enforce_required = Boolean(in_options.enforceRequired);
            const errors = {};
            const messages = [];
            const addError = (field_code, message) => {
                if (!errors[field_code]) {
                    errors[field_code] = message;
                    messages.push(message);
                }
            };

            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                if (!field_code) {
                    continue;
                }

                const field_label = field_item.field_label || field_code;
                const field_type = String(field_item.field_type || "").toLowerCase();

                if (this.isTableField(field_item)) {
                    if (enforce_required && field_item.is_required && this.getTableFieldRows(field_code).length === 0) {
                        addError(field_code, `Поле "${field_label}" должно содержать хотя бы одну строку.`);
                    }
                    continue;
                }

                const raw_value = this.getFieldValue(field_code).trim();

                if (enforce_required && field_item.is_required && raw_value === "") {
                    addError(field_code, `Поле "${field_label}" обязательно для заполнения.`);
                    continue;
                }

                if (raw_value === "") {
                    continue;
                }

                if (field_code === "inn" && !this.isValidInn(raw_value)) {
                    addError(field_code, `Поле "${field_label}" должно содержать корректный ИНН.`);
                    continue;
                }

                if (field_code === "bik" && !this.isValidBik(raw_value)) {
                    addError(field_code, `Поле "${field_label}" должно содержать корректный БИК.`);
                    continue;
                }

                if (field_code === "email" && !this.isValidOptionalEmail(raw_value)) {
                    addError(field_code, `Поле "${field_label}" должно содержать корректный e-mail.`);
                    continue;
                }

                // формат совпадает с <input type="date"> и серверной валидацией в document_template_mapper.php
                if (field_code === "contract_date" && !/^\d{4}-\d{2}-\d{2}$/.test(raw_value)) {
                    addError(field_code, `Поле "${field_label}" должно быть в формате ГГГГ-ММ-ДД.`);
                    continue;
                }

                if (field_code === "checking_account") {
                    const bik_value = this.getFieldValue("bik");
                    if (!this.isValidBik(bik_value)) {
                        addError("bik", `Для проверки поля "${field_label}" необходимо указать корректный БИК.`);
                        addError(field_code, `Поле "${field_label}" не может быть проверено без корректного БИК.`);
                        continue;
                    }

                    if (!this.isValidCheckingAccount(raw_value, bik_value)) {
                        addError(field_code, `Поле "${field_label}" не прошло проверку контрольного числа.`);
                        continue;
                    }
                }

                if (field_code === "corr_account") {
                    const bik_value = this.getFieldValue("bik");
                    if (!this.isValidBik(bik_value)) {
                        addError("bik", `Для проверки поля "${field_label}" необходимо указать корректный БИК.`);
                        addError(field_code, `Поле "${field_label}" не может быть проверено без корректного БИК.`);
                        continue;
                    }

                    if (!this.isValidCorrAccount(raw_value, bik_value)) {
                        addError(field_code, `Поле "${field_label}" не прошло проверку контрольного числа.`);
                        continue;
                    }
                }
            }

            return {
                field_errors: errors,
                messages: messages
            };
        },

        validateForAutofill() {
            const errors = {};
            const messages = [];

            const inn_value = this.getFieldValue("inn");
            const bik_value = this.getFieldValue("bik");

            if (!this.isValidInn(inn_value)) {
                errors.inn = "Введите корректный ИНН.";
                messages.push(`Поле "${this.getFieldLabel("inn")}" должно содержать корректный ИНН.`);
            }

            if (!this.isValidBik(bik_value)) {
                errors.bik = "Введите корректный БИК.";
                messages.push(`Поле "${this.getFieldLabel("bik")}" должно содержать корректный БИК.`);
            }

            const extra_validation = this.collectFieldValidationErrors({ enforceRequired: false });
            Object.keys(extra_validation.field_errors).forEach((field_code) => {
                if (!errors[field_code]) {
                    errors[field_code] = extra_validation.field_errors[field_code];
                }
            });
            for (const message of extra_validation.messages) {
                if (!messages.includes(message)) {
                    messages.push(message);
                }
            }

            return {
                field_errors: errors,
                messages: messages
            };
        },

        validateForGenerate() {
            return this.collectFieldValidationErrors({ enforceRequired: true });
        },

        /** Текущая дата для поля contract_date (требование п. 14). */
        /** Текущая дата для поля contract_date (п. 14 требований). */
        getTodayIsoDate() {
            const today = new Date();
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const day = String(today.getDate()).padStart(2, "0");
            return `${today.getFullYear()}-${month}-${day}`;
        },

        async initializeFormDataFromTemplate() {
            const next_form_data = {};
            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                if (!field_code) {
                    continue;
                }
                const field_type = String(field_item.field_type || "").toLowerCase();
                if (field_type === "table") {
                    next_form_data[field_code] = [];
                    continue;
                }

                let default_value = this.normalizeFieldValue(field_item.default_value || "");
                // при открытии шаблона подставляем сегодняшнюю дату в contract_date
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
            this.resetCacheAutoloadState();
            this.clearFieldErrors();
            this.releaseGeneratedUrl();
            this.clearDocumentPreview();
            this.clearMessage();
            await this.loadNumberPreview();
        },

        formatIsoDateForInput(in_value) {
            const raw = this.normalizeFieldValue(in_value).trim();
            if (raw === "") {
                return "";
            }
            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                return raw;
            }
            const dot_match = raw.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
            if (dot_match) {
                return `${dot_match[3]}-${dot_match[2]}-${dot_match[1]}`;
            }
            return raw;
        },

        formatDateForDisplay(in_value) {
            const iso = this.formatIsoDateForInput(in_value);
            if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
                return this.normalizeFieldValue(in_value);
            }
            const parts = iso.split("-");
            return `${parts[2]}.${parts[1]}.${parts[0]}`;
        },

        async loadIssuedRegistry() {
            this.is_loading_registry = true;
            try {
                const response = await axios.get("./queries/get_issued_documents_registry.php", {
                    params: {
                        contract_number: (this.issued_registry_filter || "").trim()
                    }
                });
                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "registry_load_failed");
                }
                this.issued_registry_rows = (response.data.rows || []).map((row_item) => ({
                    ...row_item,
                    contract_date: this.formatIsoDateForInput(row_item.contract_date),
                    spec_date: this.formatIsoDateForInput(row_item.spec_date),
                    invoice_date: this.formatIsoDateForInput(row_item.invoice_date),
                    planned_act_date: this.formatIsoDateForInput(row_item.planned_act_date)
                }));
                this.resetRegistryRowEditState();
            } catch (error) {
                this.showMessage("Не удалось загрузить реестр оформленных договоров.<br>" + String(error), "error");
            } finally {
                this.is_loading_registry = false;
            }
        },

        // Стабильный ключ строки реестра (индекс меняется после пересортировки)
        getRegistryRowKey(in_registry_row, in_row_index) {
            if (in_registry_row.spec_id) {
                return "spec-" + in_registry_row.spec_id;
            }
            if (in_registry_row.contract_id) {
                return "contract-" + in_registry_row.contract_id;
            }
            return "new-" + in_row_index;
        },

        isRegistryRowEditing(in_registry_row, in_row_index) {
            return this.issued_registry_editing_row_key === this.getRegistryRowKey(in_registry_row, in_row_index);
        },

        findRegistryEditingRowIndex() {
            return this.issued_registry_rows.findIndex((row_item, row_index) => {
                return this.isRegistryRowEditing(row_item, row_index);
            });
        },

        resetRegistryRowEditState() {
            this.issued_registry_editing_row_key = null;
            this.issued_registry_edit_snapshot = null;
        },

        startRegistryRowEdit(in_row_index) {
            const row_item = this.issued_registry_rows[in_row_index];
            if (!row_item) {
                return;
            }

            const row_key = this.getRegistryRowKey(row_item, in_row_index);
            if (this.issued_registry_editing_row_key !== null && this.issued_registry_editing_row_key !== row_key) {
                this.showMessage("Сначала сохраните или отмените редактирование текущей строки.", "error");
                return;
            }

            this.issued_registry_editing_row_key = row_key;
            this.issued_registry_edit_snapshot = { ...row_item };
        },

        cancelRegistryRowEdit(in_row_index) {
            const row_item = this.issued_registry_rows[in_row_index];
            if (!row_item) {
                return;
            }

            const is_new_row = !row_item.contract_id && !row_item.spec_id;
            if (is_new_row) {
                this.issued_registry_rows = this.issued_registry_rows.filter((_, index_item) => index_item !== in_row_index);
            } else if (this.issued_registry_edit_snapshot) {
                const rows = [...this.issued_registry_rows];
                rows[in_row_index] = { ...this.issued_registry_edit_snapshot };
                this.issued_registry_rows = rows;
            }

            this.resetRegistryRowEditState();
        },

        // «+ спец.» в заголовке: привязка к договору из редактируемой строки (поведение уточним позже)
        canAddRegistrySpecFromHeader() {
            const editing_row_index = this.findRegistryEditingRowIndex();
            if (editing_row_index < 0) {
                return false;
            }
            const editing_row = this.issued_registry_rows[editing_row_index];
            return Boolean(editing_row && String(editing_row.contract_number || "").trim());
        },

        addRegistrySpecFromHeader() {
            const editing_row_index = this.findRegistryEditingRowIndex();
            if (editing_row_index < 0) {
                this.showMessage("Откройте строку договора на редактирование, чтобы добавить спецификацию.", "error");
                return;
            }

            const editing_row = this.issued_registry_rows[editing_row_index];
            if (!editing_row || !String(editing_row.contract_number || "").trim()) {
                this.showMessage("Укажите номер договора в редактируемой строке.", "error");
                return;
            }

            this.addRegistrySpecRow(editing_row.contract_id, editing_row.contract_number);
        },

        addEmptyRegistryRow() {
            const editing_row_index = this.findRegistryEditingRowIndex();
            if (editing_row_index >= 0) {
                this.cancelRegistryRowEdit(editing_row_index);
            }

            this.issued_registry_rows = [
                {
                    spec_id: null,
                    contract_id: null,
                    contract_number: "",
                    contract_date: this.getTodayIsoDate(),
                    subject_short: "",
                    counterparty_display: "",
                    spec_number: null,
                    spec_date: "",
                    invoice_number: null,
                    invoice_date: "",
                    planned_act_date: ""
                },
                ...this.issued_registry_rows
            ];
            this.startRegistryRowEdit(0);
        },

        addRegistrySpecRow(in_contract_id, in_contract_number) {
            const editing_row_index = this.findRegistryEditingRowIndex();
            if (editing_row_index >= 0) {
                this.cancelRegistryRowEdit(editing_row_index);
            }

            this.issued_registry_rows = [
                {
                    spec_id: null,
                    contract_id: in_contract_id || null,
                    contract_number: in_contract_number || "",
                    contract_date: "",
                    subject_short: "",
                    counterparty_display: "",
                    spec_number: null,
                    spec_date: this.getTodayIsoDate(),
                    invoice_number: null,
                    invoice_date: "",
                    planned_act_date: ""
                },
                ...this.issued_registry_rows
            ];
            this.startRegistryRowEdit(0);
        },

        updateRegistryRowField(in_row_index, in_field_name, in_value) {
            const rows = [...this.issued_registry_rows];
            if (!rows[in_row_index]) {
                return;
            }
            if (!this.isRegistryRowEditing(rows[in_row_index], in_row_index)) {
                return;
            }
            rows[in_row_index] = {
                ...rows[in_row_index],
                [in_field_name]: in_value
            };
            if (in_field_name === "spec_date" && rows[in_row_index].spec_date) {
                const spec_date = rows[in_row_index].spec_date;
                const planned = new Date(spec_date);
                if (!Number.isNaN(planned.getTime())) {
                    planned.setDate(planned.getDate() + 14);
                    const month = String(planned.getMonth() + 1).padStart(2, "0");
                    const day = String(planned.getDate()).padStart(2, "0");
                    rows[in_row_index].planned_act_date = `${planned.getFullYear()}-${month}-${day}`;
                }
            }
            this.issued_registry_rows = rows;
        },

        async saveRegistryRow(in_row_index) {
            const row_item = this.issued_registry_rows[in_row_index];
            if (!row_item) {
                return;
            }

            this.is_saving_registry_row_id = in_row_index;
            try {
                const response = await axios.post("./queries/save_issued_document_registry.php", row_item);
                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "registry_save_failed");
                }
                await this.loadIssuedRegistry();
                this.showMessage("Строка реестра сохранена.", "success");
            } catch (error) {
                this.showMessage("Не удалось сохранить строку реестра.<br>" + String(error), "error");
            } finally {
                this.is_saving_registry_row_id = null;
            }
        },

        async loadNumberPreview() {
            if (!this.selected_template || !this.selected_template.template_id) {
                return;
            }

            const registry_role = this.getResolvedRegistryRole();
            if (!["contract", "specification", "invoice"].includes(registry_role)) {
                return;
            }

            try {
                const contract_date_param = this.getFieldValue("contract_date") || this.getTodayIsoDate();
                const response = await axios.get("./queries/get_document_number_preview.php", {
                    params: {
                        template_id: this.selected_template.template_id,
                        contract_date: contract_date_param,
                        contract_number: this.getFieldValue("contract_number")
                    }
                });
                if (!response.data || response.data.status !== "ok" || !response.data.preview) {
                    const api_message = response.data && response.data.message ? response.data.message : "preview_failed";
                    if (registry_role === "contract") {
                        this.showMessage(
                            "Не удалось подставить номер договора (превью).<br>" + api_message
                            + "<br>Проверьте, что применён database/migration_issued_documents.sql",
                            "error"
                        );
                    }
                    return;
                }

                const preview = response.data.preview;
                if (preview.registry_role) {
                    this.selected_template.registry_role = preview.registry_role;
                }
                if (registry_role === "contract" && preview.contract_number) {
                    this.setFieldValue("contract_number", preview.contract_number);
                }
                if (preview.spec_number !== undefined) {
                    this.setFieldValue("spec_number", String(preview.spec_number));
                }
                if (preview.invoice_number !== undefined) {
                    this.setFieldValue("invoice_number", String(preview.invoice_number));
                }
            } catch (error) {
                if (registry_role === "contract") {
                    this.showMessage(
                        "Не удалось подставить номер договора (превью).<br>" + String(error),
                        "error"
                    );
                }
            }
        },

        clearDocumentPreview() {
            this.has_document_preview = false;
            const preview_container = this.$refs.documentPreviewContainer;
            if (preview_container) {
                preview_container.innerHTML = "";
                this.resetDocumentPreviewLayoutStyles(preview_container);
            }
        },

        /** Сброс inline-стилей высоты/прокрутки (после очистки или перед новым рендером). */
        resetDocumentPreviewLayoutStyles(in_container) {
            if (!in_container) {
                return;
            }

            in_container.style.removeProperty("min-height");
            in_container.style.removeProperty("height");
            in_container.style.removeProperty("overflow");

            const preview_box = in_container.closest(".documents_preview_box");
            if (preview_box) {
                preview_box.style.removeProperty("min-height");
                preview_box.style.removeProperty("height");
                preview_box.style.removeProperty("max-height");
                preview_box.style.removeProperty("overflow");
            }
        },

        /** Рендер заполненного DOCX в область предпросмотра (библиотека docx-preview). */
        async renderDocxPreview(in_base64) {
            const preview_container = this.$refs.documentPreviewContainer;
            if (!preview_container) {
                return false;
            }

            if (!window.docx || typeof window.docx.renderAsync !== "function") {
                throw new Error("Библиотека предпросмотра DOCX не загружена");
            }

            preview_container.innerHTML = "";
            const docx_bytes = this.base64ToUint8Array(in_base64);

            await window.docx.renderAsync(
                docx_bytes.buffer,
                preview_container,
                preview_container,
                {
                    className: "docx-preview-render",
                    inWrapper: true,
                    // растягивание страницы по ширине области предпросмотра (почти на весь экран)
                    ignoreWidth: true,
                    ignoreHeight: false,
                    ignoreFonts: false,
                    breakPages: true,
                    ignoreLastRenderedPageBreak: true,
                    renderHeaders: true,
                    renderFooters: true,
                    renderFootnotes: true,
                    renderEndnotes: true
                }
            );

            this.applyDocumentPreviewScale(preview_container);
            return true;
        },

        /** Корневой узел docx-preview (класс зависит от className в renderAsync). */
        findDocxPreviewRootElement(in_container) {
            if (!in_container || !in_container.querySelector) {
                return null;
            }
            return in_container.querySelector(".docx-wrapper")
                || in_container.querySelector(".docx-preview-render-wrapper")
                || in_container.querySelector('[class$="-wrapper"]')
                || in_container.firstElementChild;
        },

        /** Увеличивает масштаб отрендеренного DOCX в просмотрщике (×2 по умолчанию). */
        applyDocumentPreviewScale(in_container) {
            const root_element = this.findDocxPreviewRootElement(in_container);
            if (!root_element || !in_container) {
                return;
            }

            const scale_value = Number(this.preview_document_scale) > 0
                ? Number(this.preview_document_scale)
                : 1.6;

            root_element.style.setProperty("transform", "scale(" + scale_value + ")", "important");
            root_element.style.setProperty("transform-origin", "top left", "important");
            root_element.style.setProperty("width", (100 / scale_value) + "%", "important");
            root_element.style.removeProperty("zoom");

            // Резервируем место под масштабированный документ: прокрутка только у экранной формы
            this.syncDocumentPreviewLayoutHeight(in_container, root_element);
        },

        /**
         * Подгоняет высоту documentPreviewContainer под полный документ (без своей полосы прокрутки).
         */
        syncDocumentPreviewLayoutHeight(in_container, in_root_element) {
            const root_element = in_root_element || this.findDocxPreviewRootElement(in_container);
            if (!in_container || !root_element) {
                return;
            }

            const apply_layout_height = () => {
                const scaled_height = Math.ceil(root_element.getBoundingClientRect().height);
                in_container.style.overflow = "visible";
                in_container.style.height = "auto";
                in_container.style.maxHeight = "none";
                in_container.style.minHeight = scaled_height > 0 ? scaled_height + "px" : "";

                const preview_box = in_container.closest(".documents_preview_box");
                if (preview_box) {
                    preview_box.style.overflow = "visible";
                    preview_box.style.height = "auto";
                    preview_box.style.maxHeight = "none";
                }
            };

            apply_layout_height();
            window.requestAnimationFrame(apply_layout_height);
            window.setTimeout(apply_layout_height, 80);
            window.setTimeout(apply_layout_height, 350);
        },

        async openEditModal() {
            if (!this.selected_template) {
                return;
            }
            await this.loadNumberPreview();
            // снимок для «Отменить» — без отката заполнения шаблона (требование п. 8)
            this.edit_modal_form_snapshot = JSON.parse(JSON.stringify(this.form_data));
            this.is_edit_modal_open = true;
            document.body.style.overflow = "hidden";
        },

        closeEditModal() {
            this.is_edit_modal_open = false;
            document.body.style.overflow = "";
        },

        cancelEditModal() {
            this.form_data = JSON.parse(JSON.stringify(this.edit_modal_form_snapshot || {}));
            this.edit_modal_form_snapshot = {};
            this.clearFieldErrors();
            this.closeEditModal();
        },

        async saveEditModal() {
            const validation = this.validateForGenerate();
            this.setFieldErrors(validation.field_errors);
            if (Object.keys(validation.field_errors).length > 0) {
                this.showMessage(validation.messages.join("<br>"), "error");
                return;
            }

            const preview_ok = await this.refreshDocumentPreview();
            if (!preview_ok) {
                return;
            }

            this.edit_modal_form_snapshot = {};
            this.closeEditModal();
            this.showMessage("Параметры сохранены. Проект документа обновлён в области предпросмотра.", "success");
        },

        /**
         * Заполнение шаблона для предпросмотра (без кэширования и скачивания).
         * @returns {Promise<boolean>}
         */
        async refreshDocumentPreview() {
            if (!this.selected_template || !this.selected_template.template_id) {
                return false;
            }

            this.is_loading_preview = true;
            this.setSpinnerVisible(true);
            try {
                const response = await axios.post("./queries/generate_document.php", {
                    template_id: this.selected_template.template_id,
                    form_data: this.form_data,
                    mode: "preview"
                });

                if (!response.data || response.data.status !== "ok") {
                    if (response.data && response.data.field_errors) {
                        this.setFieldErrors(response.data.field_errors);
                    }
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось подготовить предпросмотр документа");
                }

                const preview_base64 = response.data.document_docx_base64
                    ? response.data.document_docx_base64
                    : "";
                if (!preview_base64) {
                    throw new Error("Сервер не вернул DOCX для предпросмотра");
                }

                if (response.data.form_data && typeof response.data.form_data === "object") {
                    this.form_data = { ...this.form_data, ...response.data.form_data };
                }

                // Контейнер должен быть в DOM до вызова renderAsync
                this.has_document_preview = true;
                await this.$nextTick();
                await this.renderDocxPreview(preview_base64);

                return true;
            } catch (error) {
                this.clearDocumentPreview();
                this.showMessage("Ошибка при формировании предпросмотра.<br>" + String(error), "error");
                return false;
            } finally {
                this.is_loading_preview = false;
                this.setSpinnerVisible(false);
            }
        },

        /** Открыть режим работы с шаблонами (п. 14): скрыть реестр, показать фильтр и просмотровщик. */
        async openTemplatesView() {
            this.is_templates_view_active = true;
            this.is_documents_sidenav_expanded = false;
            this.clearMessage();

            if (this.templates.length === 0) {
                await this.loadTemplates(true);
                return;
            }

            if (!this.selected_template) {
                await this.selectTemplate(this.templates[0]);
            }
        },

        /** Сброс данных формы шаблона при отмене работы с шаблонами. */
        resetTemplatesFormState() {
            this.selected_template = null;
            this.template_fields = [];
            this.template_field_map = {};
            this.table_blocks_config = {};
            this.form_data = {};
            this.edit_modal_form_snapshot = {};
            this.resetCacheAutoloadState();
            this.clearFieldErrors();
            this.releaseGeneratedUrl();
            this.clearDocumentPreview();
        },

        /** Вернуться к реестру оформленных договоров (п. 15: «Отменить»). */
        closeTemplatesView() {
            if (this.is_edit_modal_open) {
                this.cancelEditModal();
            }
            this.resetTemplatesFormState();
            this.is_templates_view_active = false;
            this.is_documents_sidenav_expanded = false;
            this.clearMessage();
        },

        /** Клик по шильдику: открыть/свернуть панель шаблонов. */
        toggleDocumentsSidenavExpanded() {
            this.is_documents_sidenav_expanded = !this.is_documents_sidenav_expanded;
        },

        async loadTemplates(in_auto_select_first = true) {
            this.is_loading_templates = true;
            this.clearMessage();
            try {
                const response = await axios.get("./queries/get_document_templates.php");
                if (!response.data || response.data.status !== "ok") {
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось загрузить шаблоны");
                }

                this.templates = response.data.templates || [];
                this.available_categories = response.data.available_categories || [];

                if (this.templates.length > 0 && in_auto_select_first) {
                    await this.selectTemplate(this.templates[0]);
                } else if (this.templates.length === 0) {
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
            this.clearDocumentPreview();
            if (this.is_edit_modal_open) {
                this.cancelEditModal();
            }
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
                this.table_blocks_config = (response.data.template && response.data.template.table_blocks)
                    ? response.data.template.table_blocks
                    : {};
                await this.initializeFormDataFromTemplate();

                this.resetCacheAutoloadState();
                const cache_key_value = this.getCacheKeyValue();
                if (this.getTemplateCacheKeyField() === "inn" && this.isValidInn(cache_key_value)) {
                    await this.loadStoredCacheForKey(cache_key_value);
                }
            } catch (error) {
                this.showMessage("Не удалось загрузить конфигурацию выбранного шаблона.<br>" + String(error), "error");
            } finally {
                this.is_loading_template_config = false;
            }
        },

        async autofillData() {
            const cache_key_value_before = this.getCacheKeyValue();
            if (
                this.getTemplateCacheKeyField() === "inn"
                && this.isValidInn(cache_key_value_before)
                && this.isCacheableField("bik")
                && !this.isValidBik(this.getFieldValue("bik"))
            ) {
                await this.loadStoredCacheForKey(cache_key_value_before);
            }

            const validation = this.validateForAutofill();
            this.setFieldErrors(validation.field_errors);
            if (Object.keys(validation.field_errors).length > 0) {
                this.showMessage(validation.messages.join("<br>"), "error");
                return;
            }

            const inn = this.digitsOnly(this.getFieldValue("inn"));
            const bik = this.digitsOnly(this.getFieldValue("bik"));

            this.is_autofilling_data = true;
            this.setSpinnerVisible(true);
            try {
                const company_response = await axios.get("./queries/resolve_company_by_inn.php", {
                    params: {
                        inn: inn
                    }
                });

                if (!company_response.data || company_response.data.status !== "ok") {
                    throw new Error(company_response.data && company_response.data.message ? company_response.data.message : "Не удалось получить данные организации");
                }

                const bank_response = await axios.get("./queries/resolve_bank_by_bik.php", {
                    params: {
                        bik: bik
                    }
                });

                if (!bank_response.data || bank_response.data.status !== "ok") {
                    throw new Error(bank_response.data && bank_response.data.message ? bank_response.data.message : "Не удалось получить данные банка");
                }

                const company_data = company_response.data.company || {};
                this.applyDataSource(company_data);
                this.applyDataSource(bank_response.data.bank || {});
                await this.loadStoredCacheForKey(inn);

                this.setFieldValue("inn", inn);
                // не затираем БИК, подставленный из кэша при дозаполнении
                if (!this.isValidBik(this.getFieldValue("bik")) && this.isValidBik(bik)) {
                    this.setFieldValue("bik", bik);
                }

                if (!this.getFieldValue("contract_date").trim()) {
                    this.setFieldValue("contract_date", this.getTodayIsoDate());
                }

                // поля не в UI, но уходят в form_data; при генерации пересчитываются на сервере (DOCX)
                // поля не отображаются в форме, но уходят в form_data и пересчитываются на сервере при генерации DOCX
                const derived_fields = [
                    "signer_name_genitive",
                    "signer_position_genitive",
                    "signer_initials",
                    "signer_short",
                    "party_named_form",
                    "counterparty_type",
                    "counterparty_is_individual",
                    "signer_gender"
                ];
                for (const field_code of derived_fields) {
                    if (Object.prototype.hasOwnProperty.call(company_data, field_code)) {
                        this.setFieldValue(field_code, company_data[field_code]);
                    }
                }

                this.showMessage("Данные формы дозаполнены по ИНН, БИК и локальной БД.", "success");
            } catch (error) {
                this.showMessage("Ошибка при дозаполнении данных.<br>" + String(error), "error");
            } finally {
                this.is_autofilling_data = false;
                this.setSpinnerVisible(false);
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

            const validation = this.validateForGenerate();
            this.setFieldErrors(validation.field_errors);
            if (Object.keys(validation.field_errors).length > 0) {
                this.showMessage(validation.messages.join("<br>"), "error");
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
                    if (response.data && response.data.field_errors) {
                        this.setFieldErrors(response.data.field_errors);
                    }
                    throw new Error(response.data && response.data.message ? response.data.message : "Не удалось подготовить документ");
                }

                const blobUrl = this.createDocxBlobUrl(response.data);
                const fileName = this.getDownloadFileName(response.data);
                this.generated_download_url = blobUrl;
                this.generated_file_name = fileName;
                this.triggerBrowserDownload(blobUrl, fileName);
                this.clearFieldErrors();
                await this.loadIssuedRegistry();
                if (response.data.form_data) {
                    this.form_data = { ...this.form_data, ...response.data.form_data };
                }
                // п. 15: после успешного формирования — снова реестр оформленных договоров
                this.is_templates_view_active = false;
                this.is_documents_sidenav_expanded = false;
                if (this.is_edit_modal_open) {
                    this.cancelEditModal();
                }
                this.showMessage("Документ успешно сформирован и готов к скачиванию.", "success");
            } catch (error) {
                this.showMessage("Ошибка при генерации документа.<br>" + String(error), "error");
            } finally {
                this.is_generating_document = false;
                this.setSpinnerVisible(false);
            }
        },

        onContractDateInput(in_value) {
            this.setFieldValue("contract_date", in_value);
            this.loadNumberPreview();
        }
    },
    template:
    `
    <div
        class="documents_sidenav_wrap"
        v-if="is_templates_view_active"
        :class="{
            'documents_sidenav_wrap--open': isDocumentsSidenavOpen,
            'documents_sidenav_wrap--collapsed': !isDocumentsSidenavOpen
        }"
    >
        <div
            class="documents_sidenav"
            :class="{
                'documents_sidenav--open': isDocumentsSidenavOpen,
                'documents_sidenav--collapsed': !isDocumentsSidenavOpen
            }"
        >
            <div class="documents_sidenav_border" aria-hidden="true"></div>
            <div class="documents_sidenav_content">
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
        </div>

        <div
            class="documents_sidenav_badge"
            :title="is_documents_sidenav_expanded ? 'Скрыть панель' : 'Открыть шаблоны'"
            role="button"
            tabindex="0"
            @click="toggleDocumentsSidenavExpanded()"
            @keydown.enter.prevent="toggleDocumentsSidenavExpanded()"
            @keydown.space.prevent="toggleDocumentsSidenavExpanded()"
        >
            <div class="documents_sidenav_badge_text">{{ is_documents_sidenav_expanded ? 'Скрыть' : 'Шаблоны' }}</div>
        </div>
    </div>

    <div
        class="msll_body documents_body"
        :class="{
            'documents_body--registry-only': !is_templates_view_active,
            'documents_body--sidenav-open': is_templates_view_active && isDocumentsSidenavOpen,
            'documents_body--sidenav-collapsed': is_templates_view_active && !isDocumentsSidenavOpen
        }"
    >
        <div class="documents_content_card documents_registry_card" v-if="!is_templates_view_active">
            <div class="documents_registry_header">
                <h2 class="documents_title">Оформленные договоры</h2>
                <div class="documents_registry_actions">
                    <input class="msll_middle_button" type="button" value="Шаблоны документов" @click="openTemplatesView()" :disabled="is_loading_templates">
                    <input
                        class="msll_filter documents_registry_filter"
                        type="text"
                        v-model="issued_registry_filter"
                        placeholder="Фильтр по номеру договора"
                        @keyup.enter="loadIssuedRegistry()"
                    >
                    <input class="msll_middle_button" type="button" value="Применить фильтр" @click="loadIssuedRegistry()" :disabled="is_loading_registry">
                    <input class="msll_middle_button" type="button" value="Добавить договор" @click="addEmptyRegistryRow()" :disabled="is_loading_registry">
                </div>
            </div>

            <div class="documents_registry_table_wrap">
                <table class="msll_table documents_registry_table">
                    <thead>
                        <tr>
                            <th>Номер договора</th>
                            <th>Дата договора</th>
                            <th>Предмет</th>
                            <th>Контрагент</th>
                            <th>№ спец.</th>
                            <th>Дата спец.</th>
                            <th>№ счёта</th>
                            <th>Дата счёта</th>
                            <th>План. дата акта</th>
                            <th class="documents_registry_th_actions">
                                <span class="documents_registry_th_actions_label">Действия</span>
                                <input
                                    class="msll_small_button documents_registry_btn documents_registry_btn--header"
                                    type="button"
                                    value="+ спец."
                                    title="Добавить спецификацию к редактируемому договору"
                                    @click="addRegistrySpecFromHeader()"
                                    :disabled="is_loading_registry || !canAddRegistrySpecFromHeader()"
                                >
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(registry_row, row_index) in issued_registry_rows" :key="'registry-' + (registry_row.spec_id || 'c') + '-' + (registry_row.contract_id || row_index)">
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="text" :value="registry_row.contract_number" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'contract_number', $event.target.value)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="date" :value="registry_row.contract_date || ''" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'contract_date', $event.target.value)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="text" :value="registry_row.subject_short" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'subject_short', $event.target.value)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="text" :value="registry_row.counterparty_display" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'counterparty_display', $event.target.value)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="number" min="0" :value="registry_row.spec_number != null ? registry_row.spec_number : ''" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'spec_number', $event.target.value ? parseInt($event.target.value, 10) : null)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="date" :value="registry_row.spec_date || ''" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'spec_date', $event.target.value)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="number" min="0" :value="registry_row.invoice_number != null ? registry_row.invoice_number : ''" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'invoice_number', $event.target.value ? parseInt($event.target.value, 10) : null)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="date" :value="registry_row.invoice_date || ''" :readonly="!isRegistryRowEditing(registry_row, row_index)" @input="updateRegistryRowField(row_index, 'invoice_date', $event.target.value)"></td>
                            <td><input class="documents_registry_cell" :class="{ 'documents_registry_cell--readonly': !isRegistryRowEditing(registry_row, row_index) }" type="date" :value="registry_row.planned_act_date || ''" :readonly="!isRegistryRowEditing(registry_row, row_index)" :title="registry_row.planned_act_date ? formatDateForDisplay(registry_row.planned_act_date) : ''" @input="updateRegistryRowField(row_index, 'planned_act_date', $event.target.value)"></td>
                            <td class="documents_registry_row_actions">
                                <div class="documents_registry_actions_slot">
                                    <template v-if="isRegistryRowEditing(registry_row, row_index)">
                                        <input class="msll_small_button documents_registry_btn documents_registry_btn--save" type="button" value="Сохранить" @click="saveRegistryRow(row_index)" :disabled="is_saving_registry_row_id === row_index">
                                        <input class="msll_small_button documents_registry_btn documents_registry_btn--cancel" type="button" value="Отменить" @click="cancelRegistryRowEdit(row_index)" :disabled="is_saving_registry_row_id === row_index">
                                    </template>
                                    <input
                                        v-else
                                        class="msll_small_button documents_registry_btn documents_registry_btn--edit"
                                        type="button"
                                        value="Редактировать"
                                        @click="startRegistryRowEdit(row_index)"
                                        :disabled="issued_registry_editing_row_key !== null"
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!is_loading_registry && issued_registry_rows.length === 0">
                            <td colspan="10" class="documents_empty_state">Записей пока нет. Добавьте договор вручную или сформируйте документ.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="documents_status" v-if="status_message" :class="'documents_status--' + (status_type || 'info')">
                {{status_message}}
            </div>
        </div>

        <div class="documents_content_card documents_content_card--preview" v-if="is_templates_view_active && selected_template">
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
                <input class="msll_middle_button" type="button" value="Редактировать" @click="openEditModal()" :disabled="is_generating_document || is_loading_template_config || is_loading_preview || !selected_template">
                <input class="msll_middle_button" type="button" value="Сформировать DOCX" @click="generateDocument()" :disabled="is_autofilling_data || is_generating_document || is_loading_template_config || is_loading_preview || !selected_template">
                <input class="msll_middle_button" type="button" value="Отменить" @click="closeTemplatesView()" :disabled="is_generating_document || is_loading_preview">
            </div>

            <div class="documents_preview_section">
                <div class="documents_preview_title">Проект документа</div>
                <div class="documents_preview_box documents_preview_box--rendered" v-show="has_document_preview">
                    <div
                        ref="documentPreviewContainer"
                        class="documents_preview_docx_host"
                        :style="{ '--documents-preview-scale': preview_document_scale }"
                    ></div>
                </div>
                <div class="documents_preview_placeholder" v-show="!has_document_preview">
                    Нажмите «Редактировать», заполните поля и сохраните параметры — здесь появится предпросмотр заполненного шаблона (как в Word).
                </div>
            </div>

            <div class="documents_result_box" v-if="generated_download_url">
                <div class="documents_result_title">Последний сформированный файл</div>
                <a class="documents_result_link" :href="generated_download_url" :download="generated_file_name">{{generated_file_name}}</a>
            </div>
        </div>

        <div class="documents_content_card" v-else-if="is_templates_view_active">
            <div class="documents_empty_state">Выберите шаблон документа из списка слева.</div>
        </div>
    </div>

    <div
        id="documents_edit_modal"
        class="modal documents_edit_modal"
        v-if="is_edit_modal_open"
        @click.self="cancelEditModal()"
    >
        <div class="modal-content-60 documents_edit_modal_content" @click.stop>
            <div class="modal-header">
                <span class="close" @click="cancelEditModal()">&times;</span>
                <h2>Редактирование данных документа</h2>
                <div class="documents_edit_modal_subtitle" v-if="selected_template">{{selected_template.template_name}}</div>
            </div>

            <div class="modal-body documents_edit_modal_body">
                <div class="documents_form_grid documents_form_grid--modal">
                    <div
                        class="documents_form_field"
                        :class="{ 'documents_form_field--full': isTableField(field_item) }"
                        v-for="field_item in template_fields"
                        :key="'modal-' + (field_item.field_id || field_item.field_code)"
                    >
                        <label class="label-align-left documents_label">{{field_item.field_label}}</label>

                        <div v-if="isTableField(field_item)" class="documents_table_editor">
                            <table class="msll_table documents_table_field_grid">
                                <thead>
                                    <tr>
                                        <th v-for="column_code in getTableBlockColumns(field_item.field_code)" :key="field_item.field_code + '-col-' + column_code">{{column_code}}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(table_row, row_index) in getTableFieldRows(field_item.field_code)" :key="field_item.field_code + '-row-' + row_index">
                                        <td v-for="column_code in getTableBlockColumns(field_item.field_code)" :key="field_item.field_code + '-cell-' + row_index + '-' + column_code">
                                            <input
                                                class="documents_field_input"
                                                type="text"
                                                :value="table_row[column_code] || ''"
                                                @input="setTableFieldCell(field_item.field_code, row_index, column_code, $event.target.value)"
                                            >
                                        </td>
                                        <td class="documents_table_actions_cell">
                                            <input class="msll_small_button documents_table_row_delete" type="button" value="Удалить" @click="removeTableFieldRow(field_item.field_code, row_index)">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <input class="msll_middle_button documents_table_add_row" type="button" value="Добавить строку" @click="addTableFieldRow(field_item.field_code)">
                        </div>

                        <textarea
                            v-else-if="isTextareaField(field_item)"
                            :class="['documents_textarea', { 'documents_textarea--invalid': isFieldInvalidOnInput(field_item.field_code) }]"
                            :placeholder="field_item.placeholder || ''"
                            :value="getFieldValue(field_item.field_code)"
                            @input="setFieldValue(field_item.field_code, $event.target.value)"
                        ></textarea>

                        <input
                            v-else-if="isInnField(field_item.field_code)"
                            :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalidOnInput(field_item.field_code) }]"
                            type="text"
                            placeholder="Введите ИНН организации"
                            :value="getFieldValue(field_item.field_code)"
                            @input="onInnFieldInput($event.target.value)"
                            @blur="onInnFieldBlur()"
                        >

                        <template v-else-if="isCacheableField(field_item.field_code) && hasMultipleCacheOptions(field_item.field_code)">
                            <select
                                :class="['documents_field_input', 'documents_cache_select', 'documents_field_input--cache-multiple', { 'documents_field_input--invalid': isCacheFieldSelectInvalid(field_item.field_code) }]"
                                :value="getFieldValue(field_item.field_code)"
                                @change="onCacheFieldSelect(field_item.field_code, $event.target.value)"
                            >
                                <option value="">Выберите сохранённое значение</option>
                                <option
                                    v-for="option_item in getCacheFieldOptions(field_item.field_code)"
                                    :key="field_item.field_code + '-' + option_item.cache_id + '-' + option_item.value"
                                    :value="option_item.value"
                                >
                                    {{getCacheOptionLabel(option_item)}}
                                </option>
                            </select>
                            <input
                                :class="['documents_field_input', 'documents_cache_manual_input', { 'documents_field_input--invalid': isFieldInvalidOnInput(field_item.field_code) }]"
                                :type="getFieldInputType(field_item)"
                                :placeholder="field_item.placeholder || 'Или введите значение вручную'"
                                :value="getFieldValue(field_item.field_code)"
                                @input="setFieldValue(field_item.field_code, $event.target.value)"
                            >
                        </template>

                        <input
                            v-else-if="isCacheableField(field_item.field_code)"
                            :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalidOnInput(field_item.field_code) }]"
                            :type="getFieldInputType(field_item)"
                            :placeholder="getCacheFieldPlaceholder(field_item) || (field_item.placeholder || '')"
                            :value="getFieldValue(field_item.field_code)"
                            @input="setFieldValue(field_item.field_code, $event.target.value)"
                        >

                        <input
                            v-else-if="field_item.field_code === 'contract_date'"
                            :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalidOnInput(field_item.field_code) }]"
                            type="date"
                            :placeholder="field_item.placeholder || ''"
                            :value="getFieldValue(field_item.field_code)"
                            @input="onContractDateInput($event.target.value)"
                        >

                        <input
                            v-else
                            :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalidOnInput(field_item.field_code) }]"
                            :type="getFieldInputType(field_item)"
                            :placeholder="field_item.placeholder || ''"
                            :value="getFieldValue(field_item.field_code)"
                            @input="setFieldValue(field_item.field_code, $event.target.value)"
                        >

                        <div class="documents_field_hint" v-if="isComputedNumberField(field_item)">
                            Подставляется автоматически (можно изменить вручную). Окончательный номер фиксируется при «Сформировать DOCX».
                        </div>
                        <div class="documents_field_error" v-if="isFieldInvalid(field_item.field_code)">{{getFieldError(field_item.field_code)}}</div>
                        <div class="documents_field_hint documents_field_hint--stored" v-else-if="isCacheableField(field_item.field_code) && (fields_loaded_from_cache[field_item.field_code] || hasMultipleCacheOptions(field_item.field_code))">{{getCacheFieldHint(field_item.field_code)}}</div>
                        <div class="documents_field_hint" v-else-if="isInnField(field_item.field_code)">ИНН вводится вручную. Кэшируемые поля подставятся из сохранённых данных по этому ИНН, если они есть.</div>
                        <div class="documents_field_hint" v-else-if="field_item.is_required">Обязательное поле</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer documents_edit_modal_footer">
                <input class="msll_middle_button" type="button" value="Дозаполнить данные" @click="autofillData()" :disabled="is_autofilling_data || is_generating_document || is_loading_template_config || is_loading_document_cache || is_loading_preview">
                <input class="msll_middle_button" type="button" value="Сохранить" @click="saveEditModal()" :disabled="is_autofilling_data || is_generating_document || is_loading_preview">
                <input class="msll_middle_button" type="button" value="Отменить" @click="cancelEditModal()" :disabled="is_autofilling_data || is_loading_preview">
            </div>
        </div>
    </div>
    `
}