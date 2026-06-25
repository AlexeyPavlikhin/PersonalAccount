#!/usr/bin/env python3
"""Сборка components/pg_documents.js: базовая версия + явные трансформации."""
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = ROOT / "tools" / "_pg_base.js"
TARGET = ROOT / "components" / "pg_documents.js"

TRANSCRIPTS = [
    Path(r"C:\Users\VTB\.cursor\projects\c-MyProjects-msll-dev\agent-transcripts\410a12df-8e9b-4045-b780-c0a39cc1fccc\410a12df-8e9b-4045-b780-c0a39cc1fccc.jsonl"),
    Path(r"C:\Users\VTB\.cursor\projects\c-MyProjects-msll-dev\agent-transcripts\3e8227b3-34a6-4b1d-b947-f3dba99c9646\3e8227b3-34a6-4b1d-b947-f3dba99c9646.jsonl"),
    Path(r"C:\Users\VTB\.cursor\projects\c-MyProjects-msll-dev\agent-transcripts\0cda5586-e688-49bb-a5eb-15fbc92c8cfa\0cda5586-e688-49bb-a5eb-15fbc92c8cfa.jsonl"),
]


def replace(content: str, old: str, new: str, label: str) -> str:
    if old not in content:
        raise KeyError(f"Missing block: {label}")
    return content.replace(old, new, 1)


def apply_transcript_str_replaces(content: str) -> tuple[str, int, int]:
    applied = skipped = 0
    for fp in TRANSCRIPTS:
        if not fp.exists():
            continue
        for line in open(fp, encoding="utf-8"):
            if "pg_documents.js" not in line:
                continue
            try:
                obj = json.loads(line)
            except json.JSONDecodeError:
                continue
            for part in obj.get("message", {}).get("content", []):
                if not isinstance(part, dict):
                    continue
                inp = part.get("input")
                if not isinstance(inp, dict) or not inp.get("path", "").endswith("pg_documents.js"):
                    continue
                if "old_string" not in inp:
                    continue
                old, new = inp["old_string"], inp["new_string"]
                if old in content:
                    content = content.replace(old, new, 1)
                    applied += 1
                else:
                    skipped += 1
    return content, applied, skipped


def build() -> str:
    c = BASE.read_text(encoding="utf-8")

    # --- data() ---
    c = replace(
        c,
        """            form_data: {},
            is_loading_templates: false,
            is_loading_template_config: false,
            is_resolving_company: false,
            is_resolving_bank: false,
            is_generating_document: false,
            status_message: "",
            status_type: "",
            generated_download_url: "",
            generated_file_name: ""
        }
    },""",
        """            form_data: {},
            field_errors: {},
            is_loading_templates: false,
            is_loading_template_config: false,
            is_autofilling_data: false,
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
    },""",
        "data",
    )

    # --- showMessage ---
    c = replace(
        c,
        """        showMessage(in_message, in_type = "info") {
            this.status_message = in_message || "";
            this.status_type = in_type || "info";
            if (this.$root && this.$root.$refs && this.$root.$refs.ref_FormModalMessage && in_type === "error") {
                this.$root.$refs.ref_FormModalMessage.init(this, in_message);
                document.getElementById("id_FormModalMessage").style.display = "block";
            }
        },""",
        """        showMessage(in_message, in_type = "info") {
            const raw_message = in_message || "";
            this.status_message = String(raw_message).replace(/<br\\s*\\/?>/gi, " ");
            this.status_type = in_type || "info";
            if (this.$root && this.$root.$refs && this.$root.$refs.ref_FormModalMessage && in_type === "error") {
                this.$root.$refs.ref_FormModalMessage.init(this, String(raw_message));
                document.getElementById("id_FormModalMessage").style.display = "block";
            }
        },""",
        "showMessage",
    )

    # --- validation helpers (insert after normalizeFieldValue) ---
    c = replace(
        c,
        """        normalizeFieldValue(in_value) {
            if (in_value === undefined || in_value === null) {
                return "";
            }
            if (typeof in_value === "object") {
                return "";
            }
            return String(in_value);
        },

        getFieldInputType(in_field) {""",
        """        normalizeFieldValue(in_value) {
            if (in_value === undefined || in_value === null) {
                return "";
            }
            if (typeof in_value === "object") {
                return "";
            }
            return String(in_value);
        },

        digitsOnly(in_value) {
            return this.normalizeFieldValue(in_value).replace(/\\D+/g, "");
        },

        isValidInn(in_value) {
            return /^\\d{10,12}$/.test(this.digitsOnly(in_value));
        },

        isValidBik(in_value) {
            return /^\\d{9}$/.test(this.digitsOnly(in_value));
        },

        isValidOptionalEmail(in_value) {
            const value = this.normalizeFieldValue(in_value).trim();
            if (value.length === 0) {
                return true;
            }
            return /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(value);
        },

        validateAccountChecksum(seed, account) {
            if (!/^\\d{3}$/.test(seed) || !/^\\d{20}$/.test(account)) {
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
            if (!/^\\d{20}$/.test(account) || !/^\\d{9}$/.test(bik)) {
                return false;
            }
            return this.validateAccountChecksum(bik.slice(-3), account);
        },

        isValidCorrAccount(in_account, in_bik) {
            const account = this.digitsOnly(in_account);
            const bik = this.digitsOnly(in_bik);
            if (!/^\\d{20}$/.test(account) || !/^\\d{9}$/.test(bik)) {
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

        getFieldInputType(in_field) {""",
        "validation helpers",
    )

    # setFieldValue + BIK/INN helpers
    c = replace(
        c,
        """        setFieldValue(in_field_code, in_value) {
            this.form_data[in_field_code] = this.normalizeFieldValue(in_value);
        },

        applyDataSource(in_data) {""",
        """        setFieldValue(in_field_code, in_value, in_options = {}) {
            this.form_data[in_field_code] = this.normalizeFieldValue(in_value);
            this.clearFieldError(in_field_code);

            // ручное изменение БИК отменяет признак автоподстановки из БД (требование п. 8)
            if (in_field_code === "bik" && !in_options.fromStoredLoad) {
                this.bik_loaded_from_db = false;
                this.inn_for_loaded_bik = "";
            }
        },

        resetBikAutoloadState() {
            this.bik_loaded_from_db = false;
            this.inn_for_loaded_bik = "";
        },

        onInnFieldInput(in_value) {
            const inn_digits = this.digitsOnly(in_value);
            if (this.bik_loaded_from_db && inn_digits !== this.inn_for_loaded_bik) {
                this.setFieldValue("bik", "");
                this.resetBikAutoloadState();
            }
            this.setFieldValue("inn", in_value);
        },

        async onInnFieldBlur() {
            const inn = this.digitsOnly(this.getFieldValue("inn"));
            if (!this.isValidInn(inn)) {
                return;
            }
            await this.loadStoredBikForInn(inn);
        },

        /**
         * Подстановка БИК из БД по связке шаблон + ИНН (требование п. 8).
         * @returns {Promise<boolean>} true, если БИК найден и подставлен
         */
        async loadStoredBikForInn(in_inn) {
            if (!this.selected_template || !this.selected_template.template_id) {
                return false;
            }

            const inn = this.digitsOnly(in_inn);
            if (!this.isValidInn(inn)) {
                return false;
            }

            this.is_loading_stored_bik = true;
            try {
                const response = await axios.get("./queries/resolve_stored_bik.php", {
                    params: {
                        template_id: this.selected_template.template_id,
                        inn: inn
                    }
                });

                if (!response.data || response.data.status !== "ok") {
                    return false;
                }

                if (response.data.found && response.data.bik) {
                    const stored_bik = this.digitsOnly(response.data.bik);
                    if (this.isValidBik(stored_bik)) {
                        this.setFieldValue("bik", stored_bik, { fromStoredLoad: true });
                        this.bik_loaded_from_db = true;
                        this.inn_for_loaded_bik = inn;
                        return true;
                    }
                }

                return false;
            } catch (error) {
                return false;
            } finally {
                this.is_loading_stored_bik = false;
            }
        },

        getBikFieldPlaceholder(in_field) {
            if (this.bik_loaded_from_db) {
                return "Подставлено из сохранённых данных";
            }
            return (in_field && in_field.placeholder) ? in_field.placeholder : "Введите БИК банка";
        },

        getBikFieldHint() {
            if (this.bik_loaded_from_db) {
                return "БИК подставлен из сохранённых данных (шаблон + ИНН). При необходимости можно изменить.";
            }
            return "Укажите БИК, если он ещё не сохранён для данного шаблона и ИНН.";
        },

        isInnField(in_field_code) {
            return in_field_code === "inn";
        },

        isBikField(in_field_code) {
            return in_field_code === "bik";
        },

        applyDataSource(in_data) {""",
        "setFieldValue BIK",
    )

    c = replace(
        c,
        """            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                const source_field_code = field_item.source_field_code || field_code;""",
        """            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                // ИНН всегда вводится пользователем вручную (требование п. 8)
                if (field_code === "inn") {
                    continue;
                }
                const source_field_code = field_item.source_field_code || field_code;""",
        "applyDataSource skip inn",
    )

    # validation block before initializeFormDataFromTemplate
    c = replace(
        c,
        """        },

        initializeFormDataFromTemplate() {
            const next_form_data = {};
            for (const field_item of this.template_fields) {
                const field_code = field_item.field_code || "";
                if (!field_code) {
                    continue;
                }
                next_form_data[field_code] = this.normalizeFieldValue(field_item.default_value || "");
            }""",
        """        },

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

                // формат совпадает с <input type="date"> и серверной валидацией
                if (field_code === "contract_date" && !/^\\d{4}-\\d{2}-\\d{2}$/.test(raw_value)) {
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
                // при открытии шаблона подставляем сегодняшнюю дату в contract_date
                if (field_code === "contract_date" && default_value === "") {
                    default_value = this.getTodayIsoDate();
                }
                next_form_data[field_code] = default_value;
            }""",
        "validation block",
    )

    c = replace(
        c,
        """            this.form_data = next_form_data;
            this.releaseGeneratedUrl();
            this.clearMessage();
        },""",
        """            this.form_data = next_form_data;
            this.resetBikAutoloadState();
            this.clearFieldErrors();
            this.releaseGeneratedUrl();
            this.clearMessage();
        },""",
        "initializeFormData reset",
    )

    c = replace(
        c,
        """                this.initializeFormDataFromTemplate();
            } catch (error) {
                this.showMessage("Не удалось загрузить конфигурацию выбранного шаблона.<br>" + String(error), "error");
            } finally {
                this.is_loading_template_config = false;
            }
        },

        async resolveCompanyByInn() {""",
        """                this.initializeFormDataFromTemplate();

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

        async autofillData() {
            const inn_before = this.digitsOnly(this.getFieldValue("inn"));
            if (this.isValidInn(inn_before) && !this.isValidBik(this.getFieldValue("bik"))) {
                await this.loadStoredBikForInn(inn_before);
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
                this.setFieldValue("inn", inn);
                this.setFieldValue("bik", bik);

                if (!this.getFieldValue("contract_date").trim()) {
                    this.setFieldValue("contract_date", this.getTodayIsoDate());
                }

                // поля не в UI, но уходят в form_data; при генерации пересчитываются на сервере (DOCX)
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

        async resolveCompanyByInn_REMOVED() {""",
        "autofillData",
    )

    # remove old resolve methods and saveCounterparty
    c = replace(
        c,
        """        async resolveCompanyByInn_REMOVED() {
            const inn = (this.getFieldValue("inn") || "").replace(/\\D+/g, "");
            if (!/^\\d{10,12}$/.test(inn)) {
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
            const bik = (this.getFieldValue("bik") || "").replace(/\\D+/g, "");
            if (!/^\\d{9}$/.test(bik)) {
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
            const inn = (this.getFieldValue("inn") || "").replace(/\\D+/g, "");
            const checking_account = (this.getFieldValue("checking_account") || "").replace(/\\D+/g, "");

            if (!/^\\d{10,12}$/.test(inn) || !/^\\d{20}$/.test(checking_account)) {
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

        getDownloadFileName(in_payload) {""",
        """        getDownloadFileName(in_payload) {""",
        "remove old resolve",
    )

    # DOCX download
    c = replace(
        c,
        """            if (this.selected_template && this.selected_template.template_code) {
                return this.selected_template.template_code + ".pdf";
            }
            return "document.pdf";
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

        async fillPdfAndCreateBlobUrl(in_payload) {
            if (!window.PDFLib || !window.fontkit) {
                throw new Error("Не удалось загрузить PDF-библиотеки в браузере");
            }

            const pdfBytes = this.base64ToUint8Array(in_payload.template_pdf_base64);
            const pdfDoc = await window.PDFLib.PDFDocument.load(pdfBytes);
            pdfDoc.registerFontkit(window.fontkit);

            const fontResponse = await fetch("./fonts/Jost.ttf");
            if (!fontResponse.ok) {
                throw new Error("Не удалось загрузить шрифт Jost.ttf");
            }

            const fontBytes = await fontResponse.arrayBuffer();
            const customFont = await pdfDoc.embedFont(fontBytes);
            const form = pdfDoc.getForm();
            const pdfFields = in_payload.pdf_fields || {};

            Object.keys(pdfFields).forEach((fieldName) => {
                const value = this.normalizeFieldValue(pdfFields[fieldName]);
                try {
                    form.getTextField(fieldName).setText(value);
                } catch (error) {
                    console.log("Поле PDF не найдено или не является текстовым:", fieldName, error);
                }
            });

            form.updateFieldAppearances(customFont);
            form.flatten();

            const finalBytes = await pdfDoc.save();
            return URL.createObjectURL(new Blob([finalBytes], { type: "application/pdf" }));
        },""",
        """            if (this.selected_template && this.selected_template.template_code) {
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
        },""",
        "docx blob",
    )

    c = replace(
        c,
        """        async generateDocument() {
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

                const blobUrl = await this.fillPdfAndCreateBlobUrl(response.data);
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
        }""",
        """        async generateDocument() {
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
                this.showMessage("Документ успешно сформирован и готов к скачиванию.", "success");
            } catch (error) {
                this.showMessage("Ошибка при генерации документа.<br>" + String(error), "error");
            } finally {
                this.is_generating_document = false;
                this.setSpinnerVisible(false);
            }
        }""",
        "generateDocument",
    )

    # template UI
    c = replace(
        c,
        """            <motion class="documents_actions">
                <input class="msll_middle_button" type="button" value="Подтянуть по ИНН" @click="resolveCompanyByInn()" :disabled="is_resolving_company || is_generating_document || is_loading_template_config">
                <input class="msll_middle_button" type="button" value="Подтянуть по БИК" @click="resolveBankByBik()" :disabled="is_resolving_bank || is_generating_document || is_loading_template_config">
                <input class="msll_middle_button" type="button" value="Сформировать PDF" @click="generateDocument()" :disabled="is_generating_document || is_loading_template_config || !selected_template">
            </motion>""".replace("<motion ", "<div ").replace("</motion>", "</div>"),
        """            <motion class="documents_actions">
                <input class="msll_middle_button" type="button" value="Дозаполнить данные" @click="autofillData()" :disabled="is_autofilling_data || is_generating_document || is_loading_template_config || is_loading_stored_bik">
                <input class="msll_middle_button" type="button" value="Сформировать DOCX" @click="generateDocument()" :disabled="is_autofilling_data || is_generating_document || is_loading_template_config || !selected_template">
            </motion>""".replace("<motion ", "<div ").replace("</motion>", "</motion>"),
        "template actions",
    )

    # Fix if above didn't match - try without motion
    if "Дозаполнить данные" not in c:
        c = replace(
            c,
            """            <div class="documents_actions">
                <input class="msll_middle_button" type="button" value="Подтянуть по ИНН" @click="resolveCompanyByInn()" :disabled="is_resolving_company || is_generating_document || is_loading_template_config">
                <input class="msll_middle_button" type="button" value="Подтянуть по БИК" @click="resolveBankByBik()" :disabled="is_resolving_bank || is_generating_document || is_loading_template_config">
                <input class="msll_middle_button" type="button" value="Сформировать PDF" @click="generateDocument()" :disabled="is_generating_document || is_loading_template_config || !selected_template">
            </div>""",
            """            <div class="documents_actions">
                <input class="msll_middle_button" type="button" value="Дозаполнить данные" @click="autofillData()" :disabled="is_autofilling_data || is_generating_document || is_loading_template_config || is_loading_stored_bik">
                <input class="msll_middle_button" type="button" value="Сформировать DOCX" @click="generateDocument()" :disabled="is_autofilling_data || is_generating_document || is_loading_template_config || !selected_template">
            </motion>""".replace("</motion>", "</motion>"),
            "template actions div",
        )

    c = replace(
        c,
        """                    <textarea
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

                    <div class="documents_field_hint" v-if="field_item.is_required">Обязательное поле</div>""",
        """                    <textarea
                        v-if="isTextareaField(field_item)"
                        :class="['documents_textarea', { 'documents_textarea--invalid': isFieldInvalid(field_item.field_code) }]"
                        :placeholder="field_item.placeholder || ''"
                        :value="getFieldValue(field_item.field_code)"
                        @input="setFieldValue(field_item.field_code, $event.target.value)"
                    ></textarea>

                    <input
                        v-else-if="isInnField(field_item.field_code)"
                        :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalid(field_item.field_code) }]"
                        type="text"
                        placeholder="Введите ИНН организации"
                        :value="getFieldValue(field_item.field_code)"
                        @input="onInnFieldInput($event.target.value)"
                        @blur="onInnFieldBlur()"
                    >

                    <input
                        v-else-if="isBikField(field_item.field_code)"
                        :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalid(field_item.field_code) }]"
                        type="text"
                        :placeholder="getBikFieldPlaceholder(field_item)"
                        :value="getFieldValue(field_item.field_code)"
                        @input="setFieldValue(field_item.field_code, $event.target.value)"
                    >

                    <input
                        v-else
                        :class="['documents_field_input', { 'documents_field_input--invalid': isFieldInvalid(field_item.field_code) }]"
                        :type="getFieldInputType(field_item)"
                        :placeholder="field_item.placeholder || ''"
                        :value="getFieldValue(field_item.field_code)"
                        @input="setFieldValue(field_item.field_code, $event.target.value)"
                    >

                    <div class="documents_field_error" v-if="isFieldInvalid(field_item.field_code)">{{getFieldError(field_item.field_code)}}</motion>
                    <motion class="documents_field_hint documents_field_hint--stored" v-else-if="isBikField(field_item.field_code) && bik_loaded_from_db">{{getBikFieldHint()}}</motion>
                    <motion class="documents_field_hint" v-else-if="isBikField(field_item.field_code) && field_item.is_required">{{getBikFieldHint()}}</motion>
                    <motion class="documents_field_hint" v-else-if="isInnField(field_item.field_code)">ИНН вводится вручную. После ввода БИК подставится из сохранённых данных, если они есть.</motion>
                    <motion class="documents_field_hint" v-else-if="field_item.is_required">Обязательное поле</motion>""",
        "template form fields",
    )

    c = c.replace("<motion ", "<div ").replace("</motion>", "</div>")

    c, applied, skipped = apply_transcript_str_replaces(c)
    print(f"transcript str: applied={applied} skipped={skipped}")

    return c


def main() -> int:
    content = build()
    if not content.strip().startswith("export default {"):
        raise RuntimeError("Invalid output")
    TARGET.write_text(content, encoding="utf-8")
    lines = content.count("\n") + 1
    print(f"Written {TARGET} ({lines} lines)")
    return 0 if lines >= 800 else 1


if __name__ == "__main__":
    raise SystemExit(main())
